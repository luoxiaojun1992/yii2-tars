<?php

namespace Lxj\Yii2\Tars;

use yii\web\Application;

class App
{
    public static $tarsDeployCfg;

    public static $app;

    public static function setTarsDeployCfg($tarsDeployCfg)
    {
        static::$tarsDeployCfg = $tarsDeployCfg;
    }

    public static function getTarsDeployCfg()
    {
        return static::$tarsDeployCfg;
    }

    public static function getApp()
    {
        if (static::$app) {
            return static::$app;
        }
        static::setTarsDeployCfg(\Yii::$app->params['tars']['deploy_cfg']);
        static::$app = static::createApp();
        $newApp = static::$app;
        $newApp->params['tars']['deploy_cfg'] = static::getTarsDeployCfg();
        Boot::handle(true);
        return $newApp;
    }

    public static function createApp()
    {
        return new Application(include \Yii::$app->getBasePath() . '/config/web.php');
    }
}
