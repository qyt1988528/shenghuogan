<?php
namespace Maiyuan\Service\Task;
use Maiyuan\Service\Exception;
use Maiyuan\Service\Task;
class Email extends Task
{
    /**
     * validate task data
     * @param array $data
     * @return array|mixed|null
     */
    protected function _convert($data) {
        $subject = isset($data['subject']) ? $data['subject'] : null;
        if(!$subject) {
            throw new Exception("Task email 'subject' is required.");
        }
        $from = isset($data['from']) ? $data['from'] : null;
        if(!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Task email 'from' {$from} not email.");
        }
        $to = isset($data['to']) ? $data['to'] : null;
        if(!array_filter(filter_var_array($to, FILTER_VALIDATE_EMAIL))) {
            throw new Exception("Task email 'to' need email array.");
        }
        $html = isset($data['html']) ? $data['html'] : null;
        if($html && strlen($html) < 6) {
            throw new Exception("Task email html minimum 6 characters.");
        }
        $data['to'] = implode(',', $to);
        return parent::_convert($data);
    }

    /**
     * validate task data
     * @param $callback
     * @return array|mixed|null
     */
    protected function _validateCallback($callback) {
        $url = isset($callback['url']) ? $callback['url'] : null;
        if(!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("Task email callback url is not valid.");
        }
        $params = isset($callback['data']) ? $callback['data'] : null;
        if(!is_array($params)) {
            throw new Exception("Task email callback data need array type.");
        }
        return parent::_convert($callback);
    }

    /**
     * Email add data
     * @param $channel
     * @param array $data
     * @param array $callback
     * @param int $delay
     * @return mixed
     */
    public function add($channel, array $data, array $callback = [], $delay = 0) {
        if(!is_object($this->_redis)) {
            $this->_connect();
        }
        //check channels in redis server for email server
        $channels = $this->_redis->get('channels');
        if(!($channels = json_decode($channels, true))) {
            throw new Exception('Task email channels on exist.');
        }
        if(!in_array($channel, array_keys($channels))) {
            throw new Exception("Task email channel {$channel} on exist.");
        }
        $data = $this->_convert($data);
        $stack = current(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        unset($stack['object'], $stack['type'], $stack['args']);
        date_default_timezone_set('PRC');
        $data = [
            'source' => $this->_id,
            'channel' => $channel,
            'delay' =>  time() + (int)$delay,
            'add_time' => date("Y-m-d H:i:s", time()),
            'request' => $data,
            'response' => '',
            'server' => $this->_getServerInfo(),
            'stack' => $stack,
            'callback' => $callback ? $this->_validateCallback($callback) : $callback,
            'uid' => md5(uniqid($this->_id, true) . mt_rand(100000, 999999))
        ];
        if(!$this->_redis->lpush($this->_prefix . $channel, json_encode($data))) {
            throw new Exception("Task email save error.");
        }
        return $data['uid'];
    }
}