<?php

return [
//    'log_level' => ['info'],
//    'log_interval' => 1000,

    'services' => [
        'home-api' => '\app\tars\servant\PHPTest\Yii2Tars\obj\TestTafServiceServant', //根据实际情况替换，遵循PSR-4即可，与tars.proto.php配置一致
        'home-class' => '\app\tars\impl\TestTafServiceImpl', //根据实际情况替换，遵循PSR-4即可
    ],

    'proto' => [
        'appName' => 'PHPTest', //根据实际情况替换
        'serverName' => 'Yii2Tars', //根据实际情况替换
        'objName' => 'obj', //根据实际情况替换
        'withServant' => true, //决定是服务端,还是客户端的自动生成,true为服务端
        'tarsFiles' => array(
            //根据实际情况填写
            './example.tars',
        ),
        'dstPath' => '../src/tars/servant', //可替换，遵循PSR-4规则
        'namespacePrefix' => 'app\tars\servant', //可替换，遵循PSR-4规则
    ],
];
