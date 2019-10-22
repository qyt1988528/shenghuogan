<?php
namespace Reflect\Api;

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
            'Accept' => "application/json, text/plain, */*",
            'Content-Type' => "image/jpeg",
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
     * 获取上传图片链接
     * https://api.reflect.tech/api/faces/signedurl?extension=jpeg
     *
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
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        $data = $result;

        return $data;
    }

    public function getUploadUrl(){
        // 获取图片上传链接 https://api.reflect.tech/api/faces/signedurl?extension=jpeg GET
        $url = 'https://api.reflect.tech/';
        $client = new Client([
            'base_uri' => $url,
            'timeout'  => $this->app->reflect->config->reflect->timeout,
            'handler' => $this->_getStack(),
        ]);
        $uri = '/api/faces/signedurl?extension=jpeg';
        $response = $client->request('GET', $uri,[]);
        $result = $response->getBody()->getContents();
        $result = trim($result,'"');
        return $result;
    }

    public function uploadImage($url,$binary){
        $urlData = explode('.com/',$url);
        $baseUri = $urlData[0].'.com/';
        $uri = '/'.$urlData[1];
        $client = new Client([
            'base_uri' => $baseUri,
            'timeout'  => $this->app->reflect->config->reflect->timeout,
            'handler' => $this->_getStack(),
        ]);
        $params = [
            'data' => $this->app->core->api->Image()->getBlobByImageUrl('D:\image\image_wxsyb\image_20190904111134_get.jpg'),
        ];
        $response = $client->request('PUT', $uri,[
            'header' => $this->_getHeader(),
            'form_params' => $params
        ]);
        $result = $response->getBody()->getContents();
        $result = trim($result,'"');
        return $result;
    }
    public function uploadImageOther($url,$file){
        if (empty($url) || empty($file)) { return false; }
        $opts = array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
            'http' => array(
                'method' => 'PUT',
                'header' => "Content-Type:image/jpeg",
//                'header' => "Accept:application/json,text/plain,*/*;Content-Type:image/jpeg",
                'content' => $file,
                'timeout'=> $this->app->reflect->config->reflect->timeout,//单位秒
            )
        );
        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        return $response;
    }

    public function getImageInfo($imageUrl){
        // 获取图片ID https://api.reflect.tech/api/faces/addimage  header Content-Type"application/json" POST body-raw {
        //	"image_url":"https://storage.googleapis.com/prod-reflect-images/images/inputs/efdee860-9bf0-4652-96b1-8d5bb08fb17a.jpeg"
        // }
        $baseUri = 'https://api.reflect.tech/';
        $uri = '/api/faces/addimage';
        $client = new Client([
            'base_uri' => $baseUri,
            'timeout'  => $this->app->reflect->config->reflect->timeout,
            'handler' => $this->_getStack(),
        ]);
        $params = [
            'image_url' => $imageUrl,
        ];
        $response = $client->request('POST', $uri,[
            'header' => [
                'Content-Type' => "application/json",
            ],
            'form_params' => $params
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result;
    }

    public function faceFuse($params){
        // 人脸融合 https://api.reflect.tech/api/faces/swapfaces header Content-Type"application/json" POST {
        //	"image_id": "efdee860-9bf0-4652-96b1-8d5bb08fb17a",
        //	"facemapping": {
        //		"3d09d248-84cd-4460-8eec-aff2b01376a4": ["334bc4d9-3c69-4600-9cd3-a337274f9f8d"]
        //	},
        //	"tumbler": true
        //}
        /*
        $params = [
            "image_id" => "efdee860-9bf0-4652-96b1-8d5bb08fb17a",
            "facemapping" => [ "3d09d248-84cd-4460-8eec-aff2b01376a4" =>  [  "334bc4d9-3c69-4600-9cd3-a337274f9f8d" ] ],
            "tumbler" => true,
         * */
        $baseUri = 'https://api.reflect.tech/';
        $uri = '/api/faces/swapfaces';
        $client = new Client([
            'base_uri' => $baseUri,
            'timeout'  => $this->app->reflect->config->reflect->timeout,
            'handler' => $this->_getStack(),
        ]);
        $response = $client->request('POST', $uri,[
            'header' => [
                'Content-Type' => "application/json",
            ],
            'json' => $params
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result;
    }

    public function getImageUrl($url){
        if(strpos($url,'?') !== false){
            $tmpUrl = explode('?',$url);
            return $tmpUrl[0];
        }else{
            return $url;
        }
    }

    public function testSegment($params){
        $baseUri = 'https://ai.soufeel.com/';
        $uri = '/wxhj/segment';
        $client = new Client([
            'base_uri' => $baseUri,
            'timeout'  => 60,
            'handler' => $this->_getStack(),
        ]);
        $response = $client->request('POST', $uri,[
            'form_params' => $params
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result;
    }
}