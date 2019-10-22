<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;

use Phalcon\Translate\Adapter\NativeArray;

class Translate implements ServiceProviderInterface
{
    public function register(DiInterface $di) {
        $config = $di->get('config')->system;
        $lang = $di->getRequest()->getParam('_language', null, $config->language);

        $file = $di->getDir()->root("translate/{$lang}.json");
        if (!is_file($file)) {
            $translations = [];
        }else{
            $translations = json_decode(file_get_contents($file), true);
        }

        $translate = new NativeArray([
            'content'=>$translations
        ]);

        $di->setShared('translate', $translate);
        return $this;
    }

}