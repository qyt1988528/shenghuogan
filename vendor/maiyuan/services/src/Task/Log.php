<?php
namespace Maiyuan\Service\Task;
use Maiyuan\Service\Exception;
use Maiyuan\Service\Task;
class Log extends Task
{
    /**
     * validate data
     * @param $data
     * @return $this
     */
    protected function _convert($data) {
        if(!is_string($data) && !($data instanceof \Exception)) {
            throw new Exception("Task log data need string or exception type.");
        }
        return parent::_convert($data);
    }

    /**
     * Log add data
     * @param array $data
     * @return mixed
     */
    public function add($data) {
        if(!is_object($this->_redis)) {
            $this->_connect();
        }
        $data = $this->_convert($data);
        $stack = current(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        unset($stack['object'], $stack['type'], $stack['args']);
        date_default_timezone_set('PRC');
        $data = [
            'source' => $this->_id,
            'add_time' => date("Y-m-d H:i:s", time()),
            'request' => json_encode($_REQUEST),
            'response' => [
                'error' => $data instanceof \Exception ? 1 : 0,
                'body' => (string)$data
            ],
            'server' => $this->_getServerInfo(),
            'stack' => $stack,
            'uid' => md5(uniqid($this->_id, true) . mt_rand(100000, 999999))
        ];
        if(!$this->_redis->lpush($this->_prefix . "record", json_encode($data))) {
            throw new Exception("Task log save error.");
        }
        return $data['uid'];
    }
}