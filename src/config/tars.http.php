<?php

return [
    'registries' => [
//        [
//            'type' => 'kong',
//            'url' => '',
//        ]
    ],

//    'log_level' => ['info'],

    'services' => [
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

    'proto' => [
        'appName' => 'PHPTest', //根据实际情况替换
        'serverName' => 'Yii2Tars', //根据实际情况替换
        'objName' => 'obj', //根据实际情况替换
    ],
];
