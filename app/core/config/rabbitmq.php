<?php
/**
* WARNING
*
* Manual changes to this file may cause a malfunction of the system.
* Be careful when changing settings!
*
*/

/* product
//192.168.20.242  yb yangbo  ERP_TASK_AIIMAGEDOWNLOAD_QUEUE
*/
$erpRmq = [
    'host' => '192.168.20.242',
    'port' => '5672',
    'user' => 'yb',
    'password' => 'yangbo',
    'vhost'=>'merp_v2'
];
/* p1
*/
$publishProductRmq = [
    'host' => '34.205.1.111',
    'port' => '5672',
    'user' => 'maiyuan',
    'password' => 'sHvnB4tB8Cf5UWgD',
    'vhost'=>'maiyuan.image_erp_ai'
];
$publishTestRmq = [
    'host' => '113.6.252.23',
    'port' => '5672',
    'user' => 'maiyuan',
    'password' => 'maiyuan123',
    'vhost'=>'maiyuan.image_erp_ai'
];
$apiProductRmq = [
    'host' => '52.45.172.101',
    'port' => '5672',
    'user' => 'maiyuan',
    'password' => 'sHvnB4tB8Cf5UWgDz',
    'vhost'=>'maiyuan.statistics'
];
/* local
*/
$localTestRmq = [
    'host' => '127.0.0.1',
    'port' => '5672',
    'user' => 'guest',
    'password' => 'guest',
    'vhost'=>'/'
];
return [
    'erp' => $erpRmq,
    'product' => $publishProductRmq,
    'test' => $publishTestRmq,
    'api_product' => $apiProductRmq,
    'local' => $localTestRmq
];
