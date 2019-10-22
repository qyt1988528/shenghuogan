<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Logger\Adapter\File;
use Phalcon\Logger\Formatter\Line as FormatterLine;

class Logger implements ServiceProviderInterface{

    public function register(DiInterface $di) {
        $di->set('logger', function($file = 'main') {
            return new class($file, $this) extends File {

                protected $_enabled;

                public function __construct($name, $di) {
                    $config = $di->getConfig()->logger;
                    if(isset($config->enabled)) {
                        $this->_enabled = (bool) $config->enabled;
                    }
                    if($this->_enabled) {
                        $file = $di->getDir()->var('logger') . DIRECTORY_SEPARATOR . "{$name}.log";
                        $formatter = new FormatterLine($config->format);
                        $this->setFormatter($formatter);
                        parent::__construct($file);
                    }
                }

                /**
                 * Logs messages to the internal logger. Appends logs to the logger
                 * @param string $type
                 * @param null $message
                 * @param array $context
                 * @return $this
                 */
                public function log($type, $message = null,  array $context = null) {
                    if($this->_enabled) {
                        return parent::log($type, $message, $context);
                    }
                    return $this;
                }
            };
        });
    }

}