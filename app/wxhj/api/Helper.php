<?php
namespace Wxhj\Api;

use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use Nwdn\Model\NwdnCreateFaceTask;
use Nwdn\Model\NwdnCreateTaskLog;
use Psr\Http\Message\ResponseInterface;

class Helper extends Api
{
    private $_client;
    public function __construct() {
        $this->_client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->app->wxhj->config->wxhj->baseUri,
            // You can set any number of default request options.
            'timeout'  => $this->app->wxhj->config->wxhj->timeout,
            //handler
            'handler' => $this->_getStack(),
        ]);
    }

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
                $msg = $this->translate->_("NetWork Error :get response code ".$response->getStatusCode());
                throw new \Exception($msg,1);
            }
            return $response;
        };
    }

    /**
     * header with appcode
     * @return array
     */
    protected function _getHeader()
    {
        return [
            'Authorization' => 'APPCODE '.$this->app->wxhj->config->wxhj->AppCode,
        ];
    }

    /**
     * 获取请求参数
     * @param $params
     * @return array
     */
    protected function _getParams(array $params)
    {
        $data = [
            'timeStamp' => strval(time()),
        ];
        $data = array_merge($data,$params);
        return $data;
    }

    /**
     * 获取tencent ai
     * @return Client
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * 爱分割-人体头部抠图接口
     * 2200*2200 4MB
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function segment($params){
        $uri = "/segment/person/head";//人体头部抠图接口
        $params = [
            'type' => $params['image_type'],//图片类型，目前支持"jpg"和"png"两种类型
            'photo' => $params['base64'],//图片数据BASE64编码 2000*2000
        ];
        $params['return_rgba'] = 1;
        $params['is_crop_content'] = 1; //如果只需要保留人头的部分，透明部分裁剪掉，可以再加个参数：is_crop_content，设置为1
        $response = $this->_client->request('POST', $uri,[
            "json" => $params,
            "headers" => $this->_getHeader(),
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        $data = $result['data'] ?? [];

        return $data;
    }

    /**
     * 爱分割-人体头部抠图带白边
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function segmentBorder($params){
        $uri = '/segment/person/headborder';//人体头部抠图带白边
        $params = [
            'type' => $params['image_type'],//图片类型，目前支持"jpg"和"png"两种类型
            'photo' => $params['base64'],//图片数据BASE64编码 2000*2000
            'border_ratio' => $params['border'],//加边的粗细程度0-1.0之间的值，值越大，边越粗，与原图尺寸存在一定的线性关系
            'margin_color' => '#ffffff',
        ];
        $response = $this->_client->request('POST', $uri,[
            "json" => $params,
            "headers" => $this->_getHeader(),
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        $data = $result['data'] ?? [];

        return $data;
    }
}