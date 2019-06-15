<?php

namespace Lxj\Yii2\Tars;

use Illuminate\Support\Facades\Log;
use Monolog\Logger;

class Boot
{
    private static $booted = false;

    public static function handle()
    {
        if (!self::$booted) {
            $localConfig = Util::app()->params['tars'];

            list($hostname, $port, $appName, $serverName) = Util::parseTarsConfig($localConfig['deploy_cfg']);

            if (!empty($localConfig['tarsregistry'])) {
                $logLevel = isset($localConfig['log_level']) ? $localConfig['log_level'] : Logger::INFO;
                $communicatorConfigLogLevel = isset($localConfig['communicator_config_log_level']) ? $localConfig['communicator_config_log_level'] : 'INFO';

                self::fetchConfig($localConfig['tarsregistry'], $appName, $serverName, $communicatorConfigLogLevel);

                self::setTarsLog($localConfig['tarsregistry'], $appName, $serverName, $logLevel, $communicatorConfigLogLevel);
            }

            self::$booted = true;
        }
    }

    private static function fetchConfig($tarsregistry, $appName, $serverName, $logLevel = 'INFO')
    {
        $configtext = Config::fetch($tarsregistry, $appName, $serverName, $logLevel);
        if ($configtext) {
            $remoteConfig = json_decode($configtext, true);
            foreach ($remoteConfig as $configName => $configValue) {
                $app = Util::app();
                $localConfig = isset($app->params[$configName]) ? $app->params[$configName] : [];
                $app->params[$configName] = array_merge($localConfig, $configValue);
            }
        }
    }

    private static function setTarsLog($tarsregistry, $appName, $serverName, $level = Logger::INFO, $communicatorConfigLogLevel = 'INFO')
    {
        $config = Config::communicatorConfig($tarsregistry, $appName, $serverName, $communicatorConfigLogLevel);

        Util::app()->getLog()->getLogger()->dispatcher->targets['tars'] = \Yii::createObject([
            'class' => LogTarget::class,
            'logConf' => $config
        ]);
    }
}
