<?php
namespace Tencent\Api;

use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

class WeChat extends Api
{
    private $_client;
    public function __construct() {
        $this->_client = new Client([
            'base_uri' => $this->app->tencent->config->wechat->baseUri,
            'timeout'  => $this->app->tencent->config->wechat->timeout,
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
                throw new \Exception("NetWork Error :get response code ".$response->getStatusCode(),1);
            }
            $result = $response->getBody()->getContents();
            $result = json_decode($result,true);
            if($result['ret'] != 0){
                throw new \Exception($result['msg'],1);
            }
            return $response;
        };
    }

    /**
     * api 签名
     * @param $params
     * @return string
     */
    protected function _sign(array $params)
    {
        ksort($params);

        // 2. 拼按URL键值对
        $str = '';
        foreach ($params as $key => $value)
        {
            if ($value !== '')
            {
                $str .= $key . '=' . urlencode($value) . '&';
            }
        }

        // 3. 拼接app_key
        $str .= 'app_key=' . $this->app->tencent->config->tencent->appKey;

        // 4. MD5运算+转换大写，得到请求签名
        $sign = strtoupper(md5($str));
        return $sign;
    }

    /**
     * 获取请求参数
     * @param $params
     * @return array
     */
    protected function _getParams(array $params)
    {
        $data = [
            'app_id'     => $this->app->tencent->config->tencent->appId,
            'time_stamp' => strval(time()),
            'nonce_str'  => strval(rand()),
            'sign'       => '',
        ];
        $data = array_merge($data,$params);
        $data['sign'] = $this->_sign($data);
        return $data;
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
     * 判断图片是否模糊
     * @param $image
     * @param $source 用于记录调用模糊接口的来源，如:erp_consumer,记host=erp,path=consumer
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function isFuzzy($image,$source=''){
        $image = $this->getBaseImage($image);
        $params = $this->_getParams(['image' => $image]);
        $response = $this->_client->request('POST', '/fcgi-bin/image/image_fuzzy',[
            "form_params" => $params,
        ]);

        $result = $response->getBody();
        $result = json_decode($result,true);
        if(!empty($source)){
            $logDatas = explode('_',$source);
            $result['host'] = isset($logDatas[0]) ? $logDatas[0] : '';
            $result['path'] = isset($logDatas[1]) ? $logDatas[1] : '';
        }
        $data = [
            'fuzzy' => false,
            'msg' => ''
        ];
        if($result['data']['fuzzy'] == true && $result['data']['confidence'] > 0.5){
            $data['fuzzy'] = true;
            $data['msg'] = $this->translate
                ->_('Your selected image is blurry, we recommend that you change a clearer one.');
        }
        return $data;
    }

    /**
     * 图片滤镜
     * https://ai.qq.com/doc/ptuimgfilter.shtml#%E4%BA%8C%E3%80%81%E5%9B%BE%E7%89%87%E6%BB%A4%E9%95%9C%EF%BC%88ai-lab%EF%BC%89
     * @param $image
     * @param $filter
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function imgfilter($image,$filter)
    {
        $image = $this->getBaseImage($image);
        $params = $this->_getParams([
            'image' => $image,
            'filter' => (string)$filter,
            'session_id' => uniqid()
            ]);
        $logData = [
            'filter_params' => $params,
        ];
        $response = $this->_client->request('POST', '/fcgi-bin/vision/vision_imgfilter',[
            "form_params" => $params,
        ]);

        $result = $response->getBody();
        $result = json_decode($result,true);
        return ['image' => $this->getWebBaseImage($result['data']['image'])];
    }

    /**
     * 判断图片是否模糊 原样
     * @param $image
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fuzzy($image){
        $image = $this->getBaseImage($image);
        $params = $this->_getParams(['image' => $image]);
        $response = $this->_client->request('POST', '/fcgi-bin/image/image_fuzzy',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        $data = [
            'fuzzy' => $result['data']['fuzzy'],
            'confidence' => $result['data']['confidence']
        ];
        return $data;
    }

    public function getSession($jsCode){
//        GET https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_co
        $params = [
            'appid' => $this->app->tencent->config->wechat->appId,
            'secret' => $this->app->tencent->config->wechat->appSecret,
            'js_code' => $jsCode,
            'grant_type' => 'authorization_code',
        ];
        var_dump(111);

        $response = $this->_client->request('GET', '/sns/jscode2session',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        var_dump($result);
        return [];

    }

}