<?php
return [
    "debug" => 'simple', //true 原生 full phalcondebug simple 简洁
    'baseUrl' => '/',
    'profiler' => true,
    'secretKey' => 'H1MA0yB4DI9CT0',
    "isConsole" => php_sapi_name() == 'cli',
    "language" => 'en'
];