<?php
namespace Core\Service;

use Phalcon\Di;
use Phalcon\Cache\Backend\Redis;
use Phalcon\Cache\Frontend\Data as FrontData;

/**
 * Class RedisCache
 * tree
 * $this->redisCache->set(':product:c021','');
 * @package Core\Service
 */
class RedisCache
{
    protected $_data,$_di,$_frontCache,$_cache,$_config;

    public function __construct()
    {
        $this->_di = Di::getDefault();
        $this->_config = $this->_di->getConfig()->redis;
        // Cache the files for 2 days using a Data frontend
        $this->initCache($this->_config->lifetime,$this->_config->prefix);
    }

    public function initCache($lifeTime=172800,$prefix = 'cache_'){
        $this->_frontCache = new FrontData(
            [
                "lifetime" => $lifeTime,
            ]
        );
        $this->_config['prefix'] = $prefix;
        $this->_cache = new Redis(
            $this->_frontCache,
            $this->_config->toArray()
        );
    }

    public function set($key, $value,$lifetime = null,  $stopBuffer = true) {
        $this->_cache->save($key, $value,$lifetime,$stopBuffer);
    }

    public function get($key){
        return $this->_cache->get($key);
    }

    public function __call($name, $arguments)
    {
        if(method_exists($this->_cache,$name)){
            return call_user_func([$this->_cache,$name],...$arguments);
        }
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function clear(string $key = null)
    {
        if($key === null){
            return $this->_cache->flush();
        }
        $keys = $this->_cache->queryKeys($key);
        if(is_array($keys)){
            foreach ($keys as $key) {
                $key = str_replace('_PHCR', '', $key);
                $this->_cache->delete($key);
            }
        }
        return true;
    }


}