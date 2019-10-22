<?php
namespace Baidu\Api;

use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

class Helper extends Api
{
    private $_client;
    public function __construct() {
        $this->_client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->app->baidu->config->baidu->baseUri,
            // You can set any number of default request options.
            'timeout'  => $this->app->baidu->config->baidu->timeout,
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
                throw new \Exception("NetWork Error :get response code ".$response->getStatusCode(),1);
            }
            $result = $response->getBody()->getContents();
            $result = json_decode($result,true);
            if(!empty($result['error_code']) && !empty($result['error_msg'])){
                throw new \Exception($result['error_msg'],1);
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
        $str .= 'app_key=' . $this->app->baidu->config->baidu->appKey;

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
            'app_id'     => $this->app->baidu->config->baidu->appId,
            'time_stamp' => strval(time()),
            'nonce_str'  => strval(rand()),
            'sign'       => '',
        ];
        $data = array_merge($data,$params);
        $data['sign'] = $this->_sign($data);
        return $data;
    }

    /**
     * 获取baidu ai
     * @return Client
     */
    public function getClient()
    {
        return $this->_client;
    }


    private function _getAccessToken(){
        $tokenParams = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->app->baidu->config->baidu->appKey,
            'client_secret' => $this->app->baidu->config->baidu->secretKey,
        ];
        $params = $this->_getParams($tokenParams);
        $response = $this->_client->request('POST', '/oauth/2.0/token',[
            "form_params" => $params,
        ]);

        $result = $response->getBody();
        $result = json_decode($result,true);
        $data = [
            'access_token' => $result['access_token'] ?? '',
            'expires_in' => $result['expires_in'] ?? 0,// Access Token的有效期(秒为单位，一般为1个月)
            'error_code' => $result['error_code'] ?? 0,
            'error_msg' => $result['error_msg'] ?? 'success',
        ];
        return $data;
    }
    /**
     * 图片无损放大
     * 注意：图片大小不超过4M。长宽乘积不超过800p x 800px。图片的base64编码是不包含图片头的，如（data:image/jpg;base64,）
     * @param $image
     * @return array
     */
    public function imageQualityEnhance($image){
        $image = $this->app->core->api->Image()->getBaseImage($image);
        $accessTokenData = $this->_getAccessToken();
        if(empty($accessTokenData['access_token'])){
            $data = [
                'log_id' => '0',
                'image' => '',//base64str
                'error' => 1,
                'msg' => 'error accessToken',
            ];
        }else{
            $params = $this->_getParams(['image' => $image,'access_token' => $accessTokenData['access_token']]);
            $response = $this->_client->request('POST', '/rest/2.0/image-process/v1/image_quality_enhance',[
                "form_params" => $params,
            ]);

            $result = $response->getBody();
            $result = json_decode($result,true);
            $data = [
                'log_id' => isset($result['log_id']) ? (string)$result['log_id'] : '0',
                'image' => $result['image'] ?? '',//base64str
                'error_code' => $result['error_code'] ?? 0,
                'error_msg' => $result['error_msg'] ?? 'success',
            ];
        }
        return $data;
    }

}