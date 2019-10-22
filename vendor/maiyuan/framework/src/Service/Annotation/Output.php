<?php
namespace MDK\Service\Annotation;
class Output extends Abs
{
    public function filter($data) {
        $arguments = $this->_annotation->getArguments();
        $config = $this->config->output;

        //过滤器
        if (isset($arguments['rules']) && is_array($arguments['rules'])){
            $data = $this->_filter($data, $arguments['rules']);
        }
        //驼峰转化
        if (!isset($arguments['hump'])){
            $arguments['hump'] = $config->hump;
        }
        if ($arguments['hump']) {
            $data = $this->_hump($data);
        }
        return $data;
    }

    private function _filter($outputValue, $arguments)
    {
        $result = [];
        foreach ($arguments as $key => $ruleValue) {
            if (is_int($key)) {
                $key = $ruleValue;
            }
            $oldKey = $key;
            if (preg_match('/^(.*?)#(.*?)$/', $key, $match)){
                $key = $match[1];
            }
            if (is_array($ruleValue) || $key == $ruleValue) {
                if ($key == '*' ){
                    if(is_array($outputValue)){
                        foreach($outputValue AS $key => $value){
                            $result[$key] = $this->_filter($value, $ruleValue);
                        }
                    }
                    continue;
                }else{
                    if ($key == $ruleValue){
                        $value = isset($outputValue[$key]) ? $outputValue[$key] : "";
                    }else{
                        $tmpOutValue = isset($outputValue[$key])?$outputValue[$key]:'';
                        $value = $this->_filter($tmpOutValue, $ruleValue);
                    }
                }
            } else {
                $value = isset($outputValue[$key]) ? $outputValue[$key] : ($oldKey == $ruleValue ? null : $ruleValue);
            }
            if($value instanceof \Phalcon\Annotations\Annotation){
                $value = null;
            }
            if (isset($match[2])){
                $key = $match[2];
            }
            if (is_object($ruleValue)){
                $func = $ruleValue->getName();
                if (function_exists($func)){
                    $args = $ruleValue->getArguments();
                    if (empty($args)){
                        $args[0] = $value;
                    }else{
                        foreach($args AS $k=>$v){
                            if ($v == '###'){
                                $args[$k] = $value;
                            }
                        }
                    }
                    $value = $func(...$args);
                }

            }
            $result[$key] = $value;
        }
        return $result;
    }

    protected function _hump($datas = [])
    {
        if(!is_array($datas)) {
            return $datas;
        }
        $result = [];
        foreach($datas AS $key => $value) {
            $key = preg_replace_callback('/_+([a-z])/', function ($matches) {
                return strtoupper($matches[1]);
            }, $key);
            if (is_array($value)){
                $result[$key] = $this->_hump($value);
            }else{
                $result[$key] = $value;
            }
        }
        return $result;
    }
}