<?php

namespace Lxj\Yii2\Tars;

use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Tars\App;

class Boot
{
    private static $booted = false;

    public static function handle()
    {
        if (!self::$booted) {
            $localConfig = Util::app()->params['tars'];

            $deployConfig = App::getTarsConfig();
            $tarsServerConf = $deployConfig['tars']['application']['server'];
            $appName = $tarsServerConf['app'];
            $serverName = $tarsServerConf['server'];

            self::fetchConfig($localConfig['deploy_cfg'], $appName, $serverName);

            self::setTarsLog($localConfig['deploy_cfg']);

            self::$booted = true;
        }
    }

    private static function fetchConfig($deployConfigPath, $appName, $serverName)
    {
        $configtext = Config::fetch($deployConfigPath, $appName, $serverName);
        if ($configtext) {
            $remoteConfig = json_decode($configtext, true);
            foreach ($remoteConfig as $configName => $configValue) {
                $app = Util::app();
                $localConfig = isset($app->params[$configName]) ? $app->params[$configName] : [];
                $app->params[$configName] = array_merge($localConfig, $configValue);
            }
        }
    }

    private static function setTarsLog($deployConfigPath)
    {
        $config = Config::communicatorConfig($deployConfigPath);

        Util::app()->getLog()->getLogger()->dispatcher->targets['tars'] = \Yii::createObject([
            'class' => LogTarget::class,
            'logConf' => $config
        ]);
    }
}
