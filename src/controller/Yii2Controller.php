<?php

namespace Lxj\Yii2\Tars\controller;

use Lxj\Yii2\Tars\Boot;
use Lxj\Yii2\Tars\Controller;
use Lxj\Yii2\Tars\Request;
use Lxj\Yii2\Tars\Response;
use Lxj\Yii2\Tars\Util;
use yii\base\Application as Yii2App;
use yii\base\Event;

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

            Util::app()->state = Yii2App::STATE_END;
        } catch (\Exception $e) {
            $this->status(500);
            $this->sendRaw($e->getMessage() . '|' . $e->getTraceAsString());
        }
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     */
    private function handle()
    {
        ob_start();
        $isObEnd = false;

        $yii2Request = Request::make($this->getRequest())->toYii2();

        $application = Util::app();

        $tarsRequestingEvent = new Event();
        $tarsRequestingEvent->data = [$yii2Request];
        $application->trigger('tarsRequesting', $tarsRequestingEvent);
        $application->state = Yii2App::STATE_BEFORE_REQUEST;
        $application->trigger(Yii2App::EVENT_BEFORE_REQUEST);
        $application->state = Yii2App::STATE_HANDLING_REQUEST;

        $yii2Response = $application->handleRequest($yii2Request);

        $application->state = Yii2App::STATE_AFTER_REQUEST;
        $application->trigger(Yii2App::EVENT_AFTER_REQUEST);
        $application->state = Yii2App::STATE_SENDING_RESPONSE;

        ob_start();
        $yii2Response->send();
        $responseContent = ob_get_contents();
        ob_end_clean();

        if (strlen($responseContent) === 0 && ob_get_length() > 0) {
            $yii2Response->content = ob_get_contents();
            ob_end_clean();
            $isObEnd = true;
        }

        if (!$isObEnd) {
            ob_end_flush();
        }

        return [$yii2Request, $yii2Response];
    }

    private function terminate($yii2Request, $yii2Response)
    {
        //
    }

    private function clean($yii2Request)
    {
        $app = Util::app();

        if ($app->has('session', true)) {
            $app->getSession()->close();
        }
        if($app->state == -1){
            $app->getLog()->logger->flush(true);
        }
    }

    private function response($yii2Response)
    {
        $application = Util::app();

        $application->state = Yii2App::STATE_SENDING_RESPONSE;

        Response::make($yii2Response, $this->getResponse())->send();
    }
}
