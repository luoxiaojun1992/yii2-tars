<?php

namespace Lxj\Yii2\Tars\Commands;

use Lxj\Yii2\Tars\Registries\Registry;
use Lxj\Yii2\Tars\Util;
use Tars\cmd\Command as TarsCommand;
use \yii\console\Controller;

class TarsController extends Controller
{
    public function actionDeploy()
    {
        \Tars\deploy\Deploy::run();
    }

    public function actionEntry($cmd, $cfg)
    {
        list($hostname, $port, $appName, $serverName) = Util::parseTarsConfig($cfg);

        Util::app()->params['tars']['deploy_cfg'] = $cfg;

        Registry::register($hostname, $port);

        $class = new TarsCommand($cmd, $cfg);
        $class->run();
    }
}
