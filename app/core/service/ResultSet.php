<?php
namespace Core\Service;

use Phalcon\Di;
use Phalcon\Config;
use Symfony\Component\Yaml\Yaml;
use Core\Service\OutputFormat;

class ResultSet
{
    protected $_data,$_di;

    public function __construct()
    {
        $this->_di = Di::getDefault();
        $request = $this->_di->get('request');
        if(!$request instanceof  \MDK\Service\Request){
            $this->_di->register(new \MDK\Service\Request());
            $this->_di->register(new \MDK\Service\Translate());
        }
        $this->_data['header'] = [
            'serviceName' => $this->_di->get('request')->getURI(),
        ];
        $this->_data['error'] = 1;
        $this->_data['message'] = 'failed';
    }

    public function set($key, $value) {
        $this->_data[$key] = $value;
        return $this;
    }

    public function error($code,$message){
        $this->_data['error'] =  $code;
        $this->_data['message'] = $this->_di->getTranslate()->_($message);
        $this->_data['header']['rspServerTime'] = time();
        $data =  $this->_data;
        $this->_di->get('response')->setJsonContent($data)->send();
//        $this->logError();
        exit;
    }

    public function success(){
        $this->_data['error'] = 0;
        $this->_data['message'] = 'ok';
        return $this;
    }

    public function setData(array $array)
    {
        $this->_data = array_merge($this->_data,$array);
        return $this;
    }

    public function toArray(){
        $this->_data['header']['rspServerTime'] = time();
        return $this->_data;
    }

    public function toJson()
    {
        return json_encode($this->_data);
    }

    public function toObject()
    {
        return json_decode(json_encode($this->_data));
    }

    /**
     * log error
     * @param null $data
     * @return $this
     */
    public function logError($data=null)
    {
        $logger = $this->_di->getLogger('api');
        $this->request = $this->_di->get('request');
        $requestId = \Phalcon\Text::random();
        $queries = $this->request->get();
        $logger->error('['.$requestId.']request method:'.$this->request->getMethod().' query: '.json_encode($queries));
        $headers = $this->request->getHeaders();
        $logger->error('['.$requestId.']request headers host:{Host} Raw-Body:{Raw-Body} User-Agent:{User-Agent}' ,$headers);
        $rowBody = $this->request->getRawBody()?:'{}';
        //加密cvv与expireDate
        $param = json_decode($rowBody,true);
        if(isset($param['CVV'])){
            $param['CVV'] = '***';
        }
        if(isset($param['expireDate'])){
            $param['expireDate'] = '**/****';
        }
        if(isset($param['cardNumber'])){
            $param['cardNumber'] = '**** **** **** ****';
        }
        $rowBody = json_encode($param);
        $logger->error('['.$requestId.']request body:'.$rowBody);
        if(is_null($data)){
            $logger->error('['.$requestId.']response body:'.json_encode($this->_data));
        }else{
            if(!is_string($data)){
                $data = json_encode($data);
            }
            $logger->error('['.$requestId.'] end with:'.$data);
        }

        return $this;
    }

    /**
     * parse exception log message
     * @param $e
     * @return string
     */
    public function parseException($e)
    {
        $template = "[Exception] %s (File: %s Line: [%s])";
        $logMessage = sprintf($template, $e->getMessage(), $e->getFile(), $e->getLine());
        if ($trace = $e->getTraceAsString()) {
            $logMessage .= $trace . PHP_EOL;
        } else {
            $logMessage .= PHP_EOL;
        }
        return $logMessage;
    }

    public function filterByConfig($path)
    {
        $outputFIle = $this->_di->getDir()->config('output.yml');
        $yaml = Yaml::parseFile($outputFIle);
        $formater = new OutputFormat($yaml);
        $definitions = $formater->path($path,null,'/');
        $definitions = $formater->getData($definitions);
        $result= $formater->filter($definitions,$this->toArray());
        return $result;
    }
}