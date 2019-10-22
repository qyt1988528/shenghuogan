<?php
namespace Maiyuan\Service\Task;
use Maiyuan\Service\Task;
use Maiyuan\Service\Exception as Exception;
class Async extends Task
{
    /**
     * validate task data
     * @param array $data
     * @return array|mixed|null
     */
    protected function _convert($data) {
        $url = isset($data['url']) ? $data['url'] : null;
        if(!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("Task async url is not valid.");
        }
        $params = isset($data['data']) ? $data['data'] : null;
        if(!is_array($params)) {
            throw new Exception("Task async data need array type.");
        }
        return parent::_convert($data);
    }

    /**
     * task add data
     * @param $channel
     * @param array $data
     * @param int $delay
     * @return mixed
     */
    public function add($channel, array $data, $delay = 0) {
        if(!is_object($this->_redis)) {
            $this->_connect();
        }
        $data = $this->_convert($data);
        if(!is_int($delay)) {
            throw new Exception("Task async delay need int.");
        }
        $stack = current(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        unset($stack['object'], $stack['type'], $stack['args']);
        date_default_timezone_set('PRC');
        $data = [
            'origin' => $this->_id,
            'channel' => $channel,
            'delay' =>  time() + (int)$delay,
            'add_time' => date("Y-m-d H:i:s", time()),
            'request' => $data,
            'response' => '',
            'server' => $this->_getServerInfo(),
            'stack' => $stack,
            'uid' => md5(uniqid($this->_id, true) . mt_rand(100000, 999999))
        ];
        if(!$this->_redis->lpush($this->_prefix . $channel, json_encode($data))) {
            throw new Exception("Task async save error.");
        }
        return $data['uid'];
    }
}