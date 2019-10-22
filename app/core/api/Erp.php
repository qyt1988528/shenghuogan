<?php
namespace Core\Api;

use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
class Erp extends Api{
    private $_client;
    public $requestSuccess = 'succ';
    /*
    public function __construct() {
        $this->_client = new Client([
            // Base URI is used with relative requests
            //url=v2.merp.com/api // sign=2987B91FF99B16EF0A8D953E015F4ECE  app_id =MERPCP190805  method=sync_uc_clarity params=返回的信息
            //192.168.20.242
            'base_uri' => 'http://v2.merp.com/',
            // You can set any number of default request options.
            'timeout'  => 30,
            //handler
            'handler' => $this->_getStack(),
        ]);
    }*/
    protected function _getStack()
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push(Middleware::mapResponse($this->_getMapResponse()));
        return $stack;
    }

    /**
     * 响应报错处理
     * @return \Closure
     */
    protected function _getMapResponse()
    {
        return function( $response){
            if($response->getStatusCode() != 200){
                throw new \Exception("NetWork Error :get response code ".$response->getStatusCode(),1);
            }
            $result = $response->getBody()->getContents();
            $result = json_decode($result,true);
            if(empty($result)){
                throw new \Exception('Erp response result is empty',1);
            }
            if(isset($result['res']) && isset($result['msg']) && $result['res'] != $this->requestSuccess){
                throw new \Exception($result['msg'],2);
            }
            return $result;
        };
    }

    /**
     * 根据所传数据，将数据传给对应erp的地址，并获得其返回结果
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function notice($data){
        $this->_client = new Client([
            'base_uri' => $data['base_uri'],
            'timeout'  => 30,
            'handler' => $this->_getStack(),
        ]);
        unset($data['base_uri']);
        return  $this->_client->request('POST', '/api',[
            "form_params" => $data,
        ]);
    }
}
