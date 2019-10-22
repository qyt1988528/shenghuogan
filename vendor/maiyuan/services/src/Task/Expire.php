<?php
namespace Maiyuan\Service\Task;
use Maiyuan\Service\Task;
use Maiyuan\Service\Exception as Exception;
class Expire extends Task
{
    public function __construct($id, array $options) {
        //切换到 db 13
        $options['index'] = 13;
        
        parent::__construct($id, $options);

        //连接redis
        if(!is_object($this->_redis)) {
            $this->_connect();
        }
        //开启过期监听
        $expireKey = "notify-keyspace-events";
        if($this->_redis->config('GET',$expireKey)[$expireKey] == ""){
            $this->_redis->config('SET',$expireKey,"Ex");
        }
        //key规则 - app-p1-soufeel-project:expire:webp:convert:xxxxxx:expire
        
    }

    /**
     * validate task data
     * @param array $data
     * @return array|mixed|null
     */
    protected function _convertAsync($data) {
        $url = isset($data['url']) ? $data['url'] : null;
        if(!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("Task expire url is not valid.");
        }
        $params = isset($data['data']) ? $data['data'] : null;
        if(!is_array($params)) {
            throw new Exception("Task expire data need array type.");
        }
        return parent::_convert($data);
    }

    /**
     * 处理异步动作
     * @author seval
     * @param $key
     * @param $data
     * @param $delay
     * @return string
     * @throws \Exception
     */
    protected function _taskMaker($key,$action,$data,$delay){
        //批量执行redis命令
        $realKey = $this->_prefix.$action.":".$key;
        if(!$this->_redis->multi()
            ->set($realKey.":expire", $delay)
            ->set($realKey,json_encode($data))
            ->expire($realKey.":expire", $delay)
            ->exec()){
                throw new Exception("Task expire save error.");
        }
        return $realKey;
    }

    

    /**
     * task add data
     * @param $key
     * @param array $data
     * @param int $delay
     * @return string
     */
    public function addAsync($key, array $data, $delay = 0) {
        //默认北京时区
        date_default_timezone_set('PRC');

        $data = $this->_convertAsync($data);
        if(!is_int($delay)) {
            throw new Exception("Task expire delay need int.");
        }
        $stack = current(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        unset($stack['object'], $stack['type'], $stack['args']);
        
        $data = [
            'origin' => $this->_id,
            'channel' => $key,
            'delay' =>  time() + (int)$delay,
            'add_time' => date("Y-m-d H:i:s", time()),
            'request' => $data,
            'response' => '',
            'server' => $this->_getServerInfo(),
            'stack' => $stack,
            'uid' => md5(uniqid($this->_id, true) . mt_rand(100000, 999999))
        ];
        return $this->_taskMaker($key,'async.ajax',$data,$delay);
    }

    /**
     * task del data
     * @author seval
     * @param $key
     * @return string
     */
    public function delAsync($key) {
        $realKey = $this->_prefix."async.ajax:".$key;
        if(!$this->_redis->multi()
            ->del($realKey.":expire")
            ->del($realKey)
            ->exec()){
                throw new Exception("Task expire del error.");
        }else{
            return $realKey;
        }
    }
}