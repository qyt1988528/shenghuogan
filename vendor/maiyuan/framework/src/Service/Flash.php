<?php
namespace MDK\Service;
use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Flash\Direct as FlashDirect;
use Phalcon\Flash\Session as FlashSession;

class Flash implements ServiceProviderInterface
{
    public function register(DiInterface $di) {
        $flashData = [
            'error' => 'alert alert-danger',
            'success' => 'alert alert-success',
            'notice' => 'alert alert-info',
        ];
        $di->setShared('flashSession', function () use ($flashData) {
            $flash = new FlashSession($flashData);
            return $flash;
        });

        $di->setShared('flash', function () use ($flashData) {
            $flash = new FlashDirect($flashData);
            return $flash;
        });
        return $this;
    }
}