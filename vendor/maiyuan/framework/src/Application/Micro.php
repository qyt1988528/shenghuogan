<?php

namespace MDK\Application;
use MDK\Application;

/**
 * Console class.
 */
class Micro extends Application
{
    protected $_bootstrap = [
        'dir',
        'cache',
        'module',
        'config',
        'loader',
        'service',
        'event'
    ];

    protected $_service = [
        'profiler',
        'environment',
        'registry',
        'logger',
        'annotations',
        'url',
        'router',
        'dispatcher'
    ];
    public function run() {
        echo $this->handle()->getContent();
    }
}