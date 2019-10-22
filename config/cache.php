<?php

$_globalCacheConfig = [
    "enabled" => false,
    "frontend" => "\\MDK\\Cache\\Frontend\\Json",
    "backend" => "\\Phalcon\\Cache\\Backend\\File",
    "memory" => "\\Phalcon\\Cache\\Backend\\Memory",
    "lifetime" => "77700",
    "safekey" => true,
    "host" => "127.0.0.1",
    "port" => 6379,
    "lifetime" => "77700",
    "prefix" => "",
    "index" => 1,
];
return [
    "cache" => array_merge($_globalCacheConfig, [
        "enabled" => false,
        "index" => 2,
        "cacheDir" => $this->dir->var('cache/data/')
    ]),
    "systemCache" => array_merge($_globalCacheConfig, [
        "index" => 2,
        "cacheDir" => $this->dir->var('cache/system/')
    ]),
    "viewCache" => array_merge($_globalCacheConfig, [
        "enabled" => false,
        "frontend" => "\\Phalcon\\Cache\\Frontend\\Output",
        "index" => 2,
        "cacheDir" => $this->dir->var('cache/view/')
    ]),
    "modelsCache" => array_merge($_globalCacheConfig, [
        "index" => 2,
        "cacheDir" => $this->dir->var('cache/modelsCache/')
    ]),
    "modelsMetadata" => array_merge($_globalCacheConfig, [
        "backend" => "\\Phalcon\\Mvc\\Model\\Metadata\\Files",
        "memory" => "\\Phalcon\\Mvc\\Model\\Metadata\\Memory",
        "index" => 2,
        "metaDataDir" => $this->dir->var('cache/metadata/'),
    ]),
    "annotations" => array_merge($_globalCacheConfig, [
        "enabled" => false,
        "backend" => "\\Phalcon\\Annotations\\Adapter\\Files",
        "memory" => "\\Phalcon\\Annotations\\Adapter\\Memory",
        "index" => 2,
        "annotationsDir" => $this->dir->var('cache/annotations/')
    ])
];
