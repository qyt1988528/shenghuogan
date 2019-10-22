<?php
return [
    'adapter' => 'files',
    'uniqueId' => basename(get_called_class()),
    'host' => '127.0.0.1',
    'port' => 6379,
    'index' => 0,
    'prefix' => 'session_',
];