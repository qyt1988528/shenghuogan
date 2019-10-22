<?php
namespace Meitu\Api;

use Face\Model\FaceppDetectImages;
use Face\Model\FaceppDetectSingleFace;
use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

class Helper extends Api
{
    public $qiniuThumbnailKey = '-soufeel_super_image_ai';//七牛压缩样式符
    public $qiniuThumbnailInfoKey = '-soufeel_super_image_ai_info';//七牛压缩后图片信息样式符
    const IMAGE_FILE_TOO_LARGE = 'IMAGE_FILE_TOO_LARGE';
    const NO_FACE_FOUND= 'NO_FACE_FOUND';
    const BAD_FACE = 'BAD_FACE';
//    const IMAGE_DOWNLOAD_TIMEOUT = 'IMAGE_DOWNLOAD_TIMEOUT';
    public $errorMessage = [
        self::IMAGE_FILE_TOO_LARGE,
        self::NO_FACE_FOUND,
        self::BAD_FACE,
//        self::IMAGE_DOWNLOAD_TIMEOUT,
    ];
    private $_client;
    public function __construct() {

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
                throw new \Exception("Network Error. Please Try Again Later!",$response->getStatusCode());
            }
            /*
            if(isset($result['error_message'])){
                throw new \Exception($result['error_message'],1);
            }*/
            return $response;
        };
    }
    /**
     * 获取不含data:image/jpeg;base64,的base64图片
     * @param $image
     * @return bool|string
     */
    public function getBaseImage($image)
    {
        if(($start = strpos($image, ",")) !== false){
            $start ++;
            $image = substr($image, $start);
        }
        return $image;
    }

    /**
     * 获取包含data:image/jpeg;base64,的base64图片
     * @param $image
     * @return string
     */
    public function getWebBaseImage($image)
    {
        if($start = strpos($image, ",") === false){
            $image = 'data:image/jpeg;base64,'.$image;
        }
        return $image;
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
     * 美图换脸
     * @return mixed|\Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function facefuse($params){
        $client = new Client([
            'base_uri' => $this->app->meitu->config->face->baseUri,
            'timeout'  => $this->app->meitu->config->face->timeout,
            'handler' => $this->_getStack(),
        ]);


        //将$image插入数据库 获得image表的id
        $response = $client->request('GET', '/api/v1/GetAccessToken',[
            "json" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        //根据id 将result 更新到 image表里
        //根据image_id 将result 添加到 single_face表里

        return $result;
    }

    /**
     * 美图图像增强
     * @param $params
     * @return mixed|\Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function hdr($params){
        $url = 'https://openapi.mtlab.meitu.com/';
        $uri = '/v1/hdr?api_key=dttynjbjKWLIXC73LHw9Bljmt8LjRPlp&api_secret=WWg_VvUFRURjGqCoXzj0hbEtXWB8iIWn';
        $client = new Client([
            'base_uri' => $url,
            'timeout'  => $this->app->meitu->config->meitu->timeout,
            'handler' => $this->_getStack(),
        ]);


        //将$image插入数据库 获得image表的id
        $response = $client->request('POST', $uri,[
            "json" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);

        return $result;
    }
    /**
     * 美图头像分割
     * @param $params
     * @return mixed|\Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function headReplace($params){
        $url = 'https://openapi.mtlab.meitu.com/';
        $apiKey = $this->app->meitu->config->meitu->APPKEY;
        $apiSecret = $this->app->meitu->config->meitu->SecretID;
        $uri = "/v1/headreplace?api_key={$apiKey}&api_secret={$apiSecret}";
        $client = new Client([
            'base_uri' => $url,
            'timeout'  => $this->app->meitu->config->meitu->timeout,
            'handler' => $this->_getStack(),
        ]);
        $response = $client->request('POST', $uri,[
            "json" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);

        return $result;
    }





}