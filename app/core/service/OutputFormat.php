<?php
namespace Core\Service;
use Phalcon\Di;
use Phalcon\Config;
use Phalcon\Text;

class OutputFormat
{
    const TYPE_OBJECT = 'object';
    const TYPE_ARRAY = 'array';
    protected $_config;
    public function __construct(array $config)
    {
        $this->load($config);
    }

    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * 获取指定path的数据
     * @param $path
     * @param mixed $default
     * @param string $delimiter
     * @return mixed
     */
    public function path($path ,$default = null ,$delimiter = '.')
    {
        return $this->_config->path($path,$default,$delimiter);
    }

    /**
     * load config data
     * @param $config
     * @return $this
     */
    public function load($config)
    {
        $this->_config = new Config($config);
        return $this;
    }

    /**
     * 补全 yaml中的$ref 引用
     * @param Config $data
     * @return Config
     */
    public function getData(Config &$data): Config
    {
        switch ($data->type){
            case self::TYPE_OBJECT:
                $this->getProperties($data->properties);
                break;
            case self::TYPE_ARRAY:
                $data->items = $this->getData($data->items);
                break;
            default:
                break;
        }
        return $data;
    }

    /**
     * 处理$ref
     * @param $properties
     * @return Config
     */
    public function getProperties($properties): Config
    {
        foreach ($properties as &$property)
        {
            if(isset($property['$ref'])){
                $path = str_replace('#/','',$property['$ref']);
                $property = $this->_config->path($path,null,'/');
            }else{
                $property = $this->getData($property);
            }
        }
        return $properties;
    }

    private function uncamelize($camelCaps,$separator='_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    /**
     *
     * @param $definitions 数据定义
     * @param $data        待处理的数据
     * @return mixed 处理过的数据
     */
    public function filter($definitions,$data)
    {
        $isHump = isset($definitions['hump'])?$definitions['hump']:false;
        switch ($definitions->type){
            case 'string':
                $result = (string) $data;
                break;
            case 'number':
                $result = 1 * $data;
                break;
            case 'object':
                $result = [];
                foreach ($definitions['properties'] as $key => $definition)
                {
                    $result[$key] = isset($data[$key])?$data[$key]:'';
                    if($isHump){
                        $uncamelizKey = Text::uncamelize($key,'_');
                        $result[$key] = $data[$uncamelizKey];
                    }
                    if(isset($definition['alias']) && isset($data[$definition['alias']])){
                        $result[$key] = $data[$definition['alias']];
                    }
                    $result[$key] = $this->filter($definition,$result[$key]);
                }
                $result = (object)$result;
                break;
            case 'array':
                $result = [];
                if(!is_array($data)){
                    $data = array($data);
                }
                foreach ($data as $item)
                {
                    $result[] = $this->filter($definitions['items'],$item);
                }
                break;
            case 'bool':
            case 'boolean':
                $result = (boolean) $data;
                break;
            default:
                $result = $data;
                break;
        }
        if(isset($definitions->callback)){
            $func = $definitions->callback;
             $args = isset($definitions->args)?explode(',',$definitions->args):[$result];
             foreach ($args as &$arg){
                 if($arg === '###'){
                     $arg = $result;
                 }
             }
             $result = $func(...$args);
        }
        return $result;

    }
}