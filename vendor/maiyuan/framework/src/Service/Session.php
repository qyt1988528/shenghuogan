<?php
namespace MDK\Service;
use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;

use Phalcon\Session\Factory as SessionFactory;

class Session implements ServiceProviderInterface
{
    public function register(DiInterface $di) {
        $config = $di->getConfig()->session;
        if(!$config) {
            return null;
        }
        $di->setShared('session',
            function() use($config) {
                if(strtolower($config->adapter) == 'files') {
                    session_save_path($this->getDir()->var('session'));
                }
                $session = SessionFactory::load($config);
                $session->start();
                return $session;
            }
        );
        return $this;
    }
}