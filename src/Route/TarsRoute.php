<?php

namespace Lxj\Yii2\Tars\Route;

use Lxj\Yii2\Tars\Boot;
use Lxj\Yii2\Tars\Util;
use Tars\core\Request;
use Tars\core\Response;
use Tars\route\Route;
use Yii;
use yii\base\Application as Yii2App;
use yii\base\Component;
use yii\base\Event;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\debug\Module;
use yii\debug\panels\EventPanel;
use yii\web\ResponseFormatterInterface;
use yii\web\View;

class TarsRoute implements Route
{
    public function dispatch(Request $request, Response $response)
    {
        Boot::handle();

        try {
            $this->clean();

            list($yii2Request, $yii2Response) = $this->handle($request);

            $this->terminate($yii2Request, $yii2Response);

            //send response event
            $this->response($response, $yii2Response);

            Util::app()->state = Yii2App::STATE_END;

            $this->clean();
        } catch (\Exception $e) {
            $response->status(500);
            $response->send($e->getMessage() . '|' . $e->getTraceAsString());
        }
    }

    /**
     * @param Request $tarsRequest
     * @return array
     */
    protected function handle(Request $tarsRequest)
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        ob_start();
        $isObEnd = false;

        $yii2Request = \Lxj\Yii2\Tars\Request::make($tarsRequest)->toYii2();

        $application = Util::app();

        $tarsRequestingEvent = new Event();
        $tarsRequestingEvent->data = [$yii2Request];
        $application->trigger('tarsRequesting', $tarsRequestingEvent);
        $application->state = Yii2App::STATE_BEFORE_REQUEST;
        $application->trigger(Yii2App::EVENT_BEFORE_REQUEST);
        $application->state = Yii2App::STATE_HANDLING_REQUEST;

        $application->set('request', $yii2Request);
        $yii2Response = $application->handleRequest($yii2Request);

        $application->state = Yii2App::STATE_AFTER_REQUEST;
        $application->trigger(Yii2App::EVENT_AFTER_REQUEST);
        $application->state = Yii2App::STATE_SENDING_RESPONSE;

        $yii2Response->trigger(\yii\web\Response::EVENT_BEFORE_SEND);
        $this->prepareResponse($yii2Response);
        $yii2Response->trigger(\yii\web\Response::EVENT_AFTER_PREPARE);
        $yii2Response->trigger(\yii\web\Response::EVENT_AFTER_SEND);
        $yii2Response->isSent = true;

        if ((!($yii2Response->stream)) && is_null($yii2Response->content) && ob_get_length() > 0) {
            $yii2Response->content = ob_get_contents();
            ob_end_clean();
            $isObEnd = true;
        }

        if (!$isObEnd) {
            ob_end_flush();
        }

        return [$yii2Request, $yii2Response];
    }

    protected function prepareResponse(\yii\web\Response $response)
    {
        if ($response->statusCode === 204) {
            $response->content = '';
            $response->stream = null;
            return;
        }

        if ($response->stream !== null) {
            return;
        }

        if (isset($response->formatters[$response->format])) {
            $formatter = $response->formatters[$response->format];
            if (!is_object($formatter)) {
                $response->formatters[$response->format] = $formatter = Yii::createObject($formatter);
            }
            if ($formatter instanceof ResponseFormatterInterface) {
                $formatter->format($response);
            } else {
                throw new InvalidConfigException("The '{$response->format}' response formatter is invalid. It must implement the ResponseFormatterInterface.");
            }
        } elseif ($response->format === \yii\web\Response::FORMAT_RAW) {
            if ($response->data !== null) {
                $response->content = $response->data;
            }
        } else {
            throw new InvalidConfigException("Unsupported response format: {$response->format}");
        }

        if (is_array($response->content)) {
            throw new InvalidArgumentException('Response content must not be an array.');
        } elseif (is_object($response->content)) {
            if (method_exists($response->content, '__toString')) {
                $response->content = $response->content->__toString();
            } else {
                throw new InvalidArgumentException('Response content must be a string or an object implementing __toString().');
            }
        }
    }

    protected function terminate($yii2Request, $yii2Response)
    {
        $tarsRequestedEvent = new Event();
        $tarsRequestedEvent->data = [$yii2Request, $yii2Response];
        Util::app()->trigger('tarsRequested', $tarsRequestedEvent);
    }

    protected function clean()
    {
        clearstatcache();

        $app = Util::app();

        if ($app->has('session', true)) {
            $session = $app->getSession();
            $session->close();
            $this->clearBehaviors($session);
        }
        if ($app->state == -1) {
            if ($app->has('log', true)) {
                $log = $app->getLog();
                $log->logger->flush(true);
                $this->clearBehaviors($log);
            }
        }
        if ($app->has('response', true)) {
            $response = $app->getResponse();
            $response->off(\yii\web\Response::EVENT_AFTER_PREPARE);
            $response->clear();
            $this->clearBehaviors($response);
        }

        if (!is_null($app->controller)) {
            if (!is_null($app->controller->action)) {
                $this->clearBehaviors($app->controller->action);
            }

            $modules = array_merge($app->getModules(true), $app->controller->getModules());
            foreach ($modules as $module) {
                if (!is_null($module)) {
                    if ($module instanceof Module) {
                        if (!is_null($module->logTarget)) {
                            $module->logTarget->messages = [];
                        }
                        $panels = $module->panels;
                        if (is_array($panels)) {
                            foreach ($panels as $panel) {
                                if ($panel instanceof EventPanel) {
                                    $eventPanelReflection = new \ReflectionObject($panel);
                                    $eventPanelEventsReflection = $eventPanelReflection->getProperty('_events');
                                    $eventPanelEventsReflection->setAccessible(true);
                                    $eventPanelEventsReflection->setValue($panel, []);
                                    break;
                                }
                            }
                        }
                    }
                    $this->clearBehaviors($module);
                }
            }

            $this->clearBehaviors($app->controller);
            $app->controller->action = null;
        }

        $app->controller = null;
        $app->requestedAction = null;

        if ($app->has('view', true)) {
            $view = $app->getView();
            $view->off(View::EVENT_END_BODY);
            $this->clearBehaviors($view);
        }

        $this->clearBehaviors($app);
    }

    /**
     * @param Component $component
     * @throws \ReflectionException
     */
    protected function clearBehaviors($component)
    {
        if (!is_object($component)) {
            return;
        }
        $componentReflection = new \ReflectionObject($component);
        $componentReflection = $this->getParentComponent($componentReflection);
        if (is_null($componentReflection)) {
            return;
        }
        foreach ($component->getBehaviors() as $behavior) {
            if (is_object($behavior)) {
                $behavior->detach();
            }
        }
        $componentBehaviorsReflection = $componentReflection->getProperty('_behaviors');
        $componentBehaviorsReflection->setAccessible(true);
        $componentBehaviorsReflection->setValue($component, null);
    }

    /**
     * @param \ReflectionObject|\ReflectionClass $componentReflection
     * @return \ReflectionObject|\ReflectionClass|null
     */
    protected function getParentComponent($componentReflection)
    {
        if ($componentReflection->getName() === Component::class) {
            return $componentReflection;
        }

        $parentReflection = $componentReflection->getParentClass();
        if ($parentReflection === false) {
            return null;
        }

        return $this->getParentComponent($parentReflection);
    }

    protected function response($tarsResponse, $yii2Response)
    {
        $application = Util::app();

        $application->state = Yii2App::STATE_SENDING_RESPONSE;

        \Lxj\Yii2\Tars\Response::make($yii2Response, $tarsResponse)->send();
    }
}
