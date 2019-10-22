<?php
namespace Core\Service;

use Phalcon\Di;
use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Cache\Frontend\Data as FrontData;

class Cache
{
    protected $_data,$_di,$_frontCache,$_cache;

    public function __construct()
    {
        $this->_di = Di::getDefault();
        // Cache the files for 2 days using a Data frontend
        $this->initCache();
    }

    public function initCache($lifeTime=172800,$path='cache/data/'){
        $this->_frontCache = new FrontData(
            [
                "lifetime" => $lifeTime,
            ]
        );
        $dir = $this->_di->getDir()->var($path);
        $this->_cache = new BackFile(
            $this->_frontCache,
            [
                "cacheDir" => $dir,
            ]
        );
    }

    public function set($key, $value) {
        $this->_cache->save($key, $value);
    }

    public function get($key){
        return $this->_cache->get($key);
    }


}