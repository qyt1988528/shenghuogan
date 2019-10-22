<?php
namespace Maiyuan\Tool\Http;

use Maiyuan\Tool\Http\Exception;

class Curl {

    protected $_curl;

    protected $_baseUrl;

    protected $_error;

    /**
     * Constructor ensures the available curl extension is loaded.
     * @throws \ErrorException
     */
    public function __construct() {
        if (!extension_loaded('curl')) {
            throw new Exception('The cURL extensions is not loaded.');
        }
        $this->_curl = curl_init();
        $this->setOptions([
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            // 设置超时限制防止死循环
            CURLOPT_TIMEOUT => 30
        ]);
        $this->setHeader('Content-Type', 'application/json');
    }

    /**
     * 处理结果
     * @param $response
     * @return mixed
     */
    protected function _process($response) {
        if($this->_error) {
            throw new Exception($this->_error);
        }
        if($result = json_decode($response, true)) {
            return $result;
        }
        return $response;
    }

    /**
     * Execute the curl request based on the respectiv settings.
     * @return int Returns the error code for the current curl request
     */
    protected function _exec() {
        $response = curl_exec($this->_curl);
        $httpStatusCode = $this->getOption(CURLINFO_HTTP_CODE);
        $httpError = in_array(floor($httpStatusCode / 100), array(4, 5));
        if($httpError) {
            $this->_error = "Http Error Status Code {$httpStatusCode}";
        }

        $curlError = !(curl_errno($this->_curl) === 0);
        if($curlError) {
            $this->_error = curl_error($this->_curl);
        }
        return $response;
    }

    protected function _send($url, array $data = [], $method = 'GET') {
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method)
        ];
        if (strtolower($method) == 'get' && (!empty($data) && $query = http_build_query($data))){
            $options[CURLOPT_URL] .= "?{$query}";
            $options[CURLOPT_POSTFIELDS] = null;
        }else{
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        $this->setOptions($options);
        return $this->_process($this->_exec());
    }


    public function getOption($option) {
        return curl_getinfo($this->_curl, $option);
    }

    public function setOptions($options) {
        foreach ($options as $key => $value) {
            curl_setopt($this->_curl, $key, $value);
        }
        return $this;
    }

    public function setBaseUrl($baseUrl) {
        $this->_baseUrl = $baseUrl;
        return $this;
    }

    public function setCookie($key, $value) {
        $this->_cookies[$key] = $value;
        $this->setOptions([
            CURLOPT_COOKIE => http_build_query($this->_cookies, '', '; ')
        ]);
        return $this;
    }

    public function setHeader($key, $value) {
        $this->_headers[$key] = $key.': '.$value;
        $this->setOptions([
            CURLOPT_HTTPHEADER => array_values($this->_headers)
        ]);
        return $this;
    }

    public function setRawBody(array $rawBody) {
        $this->setHeader('RAW-BODY', base64_encode(json_encode($rawBody)));
        return $this;
    }

    /**
     * main method to send
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        $name = strtolower($name);
        $methods = [
            'get',
            'post',
            'delete',
            'put'
        ];
        if(!in_array($name, $methods)) {
            throw new Exception("Method {$name} not allowed");
        }
        list($url, $condition) = $arguments;
        if(!$url) {
            throw new Exception("Curl arguments url or condition not null");
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if(!in_array($scheme, ['http', 'https'])) {
            $url = $this->_baseUrl . $url;
        }
        return $this->_send($url, is_array($condition) ? $condition : [], $name);
    }

    public function __destruct() {
        if (is_resource($this->_curl)) {
            curl_close($this->_curl);
        }
    }
}


