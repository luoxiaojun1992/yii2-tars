<?php

namespace Lxj\Yii2\Tars\controller;

use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;
use Lxj\Yii2\Tars\Boot;
use Lxj\Yii2\Tars\Controller;
use Lxj\Yii2\Tars\Request;
use Lxj\Yii2\Tars\Response;
use Lxj\Yii2\Tars\Util;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use yii\base\Application as Yii2App;

class Yii2Controller extends Controller
{
    public function actionRoute()
    {
        Boot::handle();

        try {
            clearstatcache();

            list($yii2Request, $yii2Response) = $this->handle();

            $this->terminate($yii2Request, $yii2Response);

            $this->clean($yii2Request);

            //send response event
            $this->response($yii2Response);
        } catch (\Exception $e) {
            $this->status(500);
            $this->sendRaw($e->getMessage() . '|' . $e->getTraceAsString());
        }

        Util::app()->state = Yii2App::STATE_END;
    }

    private function handle()
    {
        ob_start();
        $isObEnd = false;

        $illuminateRequest = Request::make($this->getRequest())->toIlluminate();

        event('laravel.tars.requesting', [$illuminateRequest]);

        $application = Util::app();

        $application->state = Yii2App::STATE_BEFORE_REQUEST;
        $application->trigger(Yii2App::EVENT_BEFORE_REQUEST);

        $application->state = Yii2App::STATE_HANDLING_REQUEST;
        $response = $this->handleRequest($illuminateRequest);

        $application->state = Yii2App::STATE_AFTER_REQUEST;
        $application->trigger(Yii2App::EVENT_AFTER_REQUEST);

        $application->state = Yii2App::STATE_SENDING_RESPONSE;

        if (!($illuminateResponse instanceof BinaryFileResponse)) {
            $content = $illuminateResponse->getContent();
            if (strlen($content) === 0 && ob_get_length() > 0) {
                $illuminateResponse->setContent(ob_get_contents());
                ob_end_clean();
                $isObEnd = true;
            }
        }

        if (!$isObEnd) {
            ob_end_flush();
        }

        return [$illuminateRequest, $illuminateResponse];
    }

    private function terminate($illuminateRequest, $illuminateResponse)
    {
        $application = Util::app();

        if (Util::isLumen()) {
            // Reflections
            $reflection = new \ReflectionObject($application);

            $middleware = $reflection->getProperty('middleware');
            $middleware->setAccessible(true);

            $callTerminableMiddleware = $reflection->getMethod('callTerminableMiddleware');
            $callTerminableMiddleware->setAccessible(true);

            if (count($middleware->getValue($application)) > 0) {
                $callTerminableMiddleware->invoke($application, $illuminateResponse);
            }
        } else {
            /** @var Kernel $kernel */
            $kernel = $application->make(Kernel::class);
            $kernel->terminate($illuminateRequest, $illuminateResponse);
        }

        event('laravel.tars.requested', [$illuminateRequest, $illuminateResponse]);
    }

    private function clean($illuminateRequest)
    {
        if ($illuminateRequest->hasSession()) {
            $session = $illuminateRequest->getSession();
            if (is_callable([$session, 'clear'])) {
                $session->clear(); // @codeCoverageIgnore
            } else {
                $session->flush();
            }
        }

        $application = Util::app();

        if (Util::isLumen()) {
            // Clean laravel cookie queue
            if ($application->has('cookie')) {
                $cookieJar = $application->make('cookie');
                foreach ($cookieJar->getQueuedCookies() as $name => $cookie) {
                    $cookieJar->unqueue($name);
                }
            }

            // Reflections
            $reflection = new \ReflectionObject($application);
            $loadedProviders = $reflection->getProperty('loadedProviders');
            $loadedProviders->setAccessible(true);
            $loadedProvidersValue = $loadedProviders->getValue($application);
            if (array_key_exists(AuthServiceProvider::class, $loadedProvidersValue)) {
                unset($loadedProvidersValue[AuthServiceProvider::class]);
                $loadedProviders->setValue($application, $loadedProvidersValue);
                $application->register(AuthServiceProvider::class);
                Facade::clearResolvedInstance('auth');
            }
        } else {
            // Clean laravel cookie queue
            $cookies = $application->make(QueueingFactory::class);
            foreach ($cookies->getQueuedCookies() as $name => $cookie) {
                $cookies->unqueue($name);
            }

            if ($this->app->isProviderLoaded(AuthServiceProvider::class)) {
                $this->app->register(AuthServiceProvider::class, [], true);
                Facade::clearResolvedInstance('auth');
            }
        }
    }

    private function response($illuminateResponse)
    {
        $application = Util::app();

        $application->state = Yii2App::STATE_SENDING_RESPONSE;

        Response::make($illuminateResponse, $this->getResponse())->send();
    }
}
