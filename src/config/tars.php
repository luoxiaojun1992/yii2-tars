<?php

return [
    'registries' => [
//        [
//            'type' => 'kong',
//            'url' => '',
//        ]
    ],

//    'log_level' => ['info'],
//    'log_interval' => 1000,

    'services' => [
        'obj' => [
            'protocolName' => 'http', //http, json, tars or other
            'serverType' => 'http', //http(no_tars default), websocket, tcp(tars default), udp
            'namespaceName' => 'Lxj\Yii2\Tars\\',
            'monitorStoreConf' => [
                //'className' => Tars\monitor\cache\RedisStoreCache::class,
                //'config' => [
                // 'host' => '127.0.0.1',
                // 'port' => 6379,
                // 'password' => ':'
                //],
                'className' => Tars\monitor\cache\SwooleTableStoreCache::class,
                'config' => [
                    'size' => 40960
                ]
            ],
        ],
        'tarsObj' => [
            'protocolName' => 'tars', //http, json, tars or other
            'serverType' => 'tcp', //http(no_tars default), websocket, tcp(tars default), udp
            'home-api' => '\app\tars\servant\PHPTest\Yii2Tars\tarsObj\TestTafServiceServant', //根据实际情况替换，遵循PSR-4即可，与tars.proto.php配置一致
            'home-class' => '\app\tars\impl\TestTafServiceImpl', //根据实际情况替换，遵循PSR-4即可
        ],
    ],

    'proto' => [
        'appName' => 'PHPTest', //根据实际情况替换
        'serverName' => 'Yii2Tars', //根据实际情况替换
        'objName' => 'tarsObj', //根据实际情况替换
        'withServant' => true, //决定是服务端,还是客户端的自动生成,true为服务端
        'tarsFiles' => array(
            //根据实际情况填写
            './example.tars',
        ),
        'dstPath' => '../src/tars/servant', //可替换，遵循PSR-4规则
        'namespacePrefix' => 'app\tars\servant', //可替换，遵循PSR-4规则
    ],
];
