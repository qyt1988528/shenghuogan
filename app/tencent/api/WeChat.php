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
            if(isset($result['errcode']) &&  $result['errcode']!= 0){
                throw new \Exception($result['errmsg'],1);
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



    public function getSessionByCode($jsCode){
        $params = [
            'appid' => $this->app->tencent->config->wechat->appId,
            'secret' => $this->app->tencent->config->wechat->appSecret,
            'js_code' => $jsCode,
            'grant_type' => 'authorization_code',
        ];
        $uri =  'sns/jscode2session';
        $url = $this->app->tencent->config->wechat->baseUri.$uri.'?'. http_build_query($params);

        $sessionInfo = $this->app->core->api->Image()->imageFileGetContents($url);
        //wx.login({success (res){console.log(res)}});
        // $json = '{"session_key":"jHwS8qG\/Gwn40YJ6Zhevwg==","openid":"oSbAs5NzFZyCiez1WZNm3JkjCeH4"}';
        // $json = '{"errcode":40029,"errmsg":"invalid code, hints: [ req_id: ihlEY24ce-GYdrra ]"}';
        if(empty($sessionInfo) || (isset($sessionInfo['errcode']) &&  $sessionInfo['errcode']!= 0) ){
            return [];
        }else{
            $sessionInfo = json_decode($sessionInfo,true);
            if(isset($sessionInfo['session_key']) && isset($sessionInfo['openid'])){
                $this->app->tencent->api->User()->getInfoByOpenid($sessionInfo['openid'],$sessionInfo['session_key']);
                return $sessionInfo;
            }else{
                return [];
            }
        }
    }
    /*
    public function getSession($jsCode){
//        GET https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_co
        $params = [
            'appid' => $this->app->tencent->config->wechat->appId,
            'secret' => $this->app->tencent->config->wechat->appSecret,
            'js_code' => $jsCode,
            'grant_type' => 'authorization_code',
        ];
        $uri =  '/sns/jscode2session';
        $uri =  'sns/jscode2session';
        $uri = $uri.'?'. http_build_query($params);
//        var_dump($params);
//        var_dump('111.test');

        $url = $this->app->tencent->config->wechat->baseUri.$uri;
//        var_dump($url);exit;
        $imageOriginalInfo = $this->app->core->api->Image()->imageFileGetContents($url);
//        $imageOriginalInfo = $this->app->core->api->Image()->getInfo($url);
        var_dump($imageOriginalInfo);exit;
        $response = $this->_client->request('GET',$uri ,[
//            "form_params" => $params,
        ]);
        var_dump($response);
        $result = $response->getBody();
        var_dump($result);
        $result = $response->getBody()->getContents();
        var_dump($result);
        return [];

    }

        //第一步：用户同意授权，获取code
    public function redirectToCode($redirectUrl, $scope = 'snsapi_userinfo'){
        $params = [
            'appid' => $this->_config->appKey,
            'redirect_uri' => $redirectUrl,
            'response_type' => 'code',
            'scope' => $scope,
        ];
        $url = $this->_config->openHost . 'connect/oauth2/authorize'
            . (empty($params) ? '' : '?' . http_build_query($params))
            . '#wechat_redirect';
        header('Location: ' . $url);
        exit;
    }
    //第二步：通过code换取网页授权access_token
    public function getAccessToken($code){
        $params = [
            'appid' => $this->_config->appKey,
            'secret' => $this->_config->appSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        return $this->_request($this->getUrl('sns/oauth2/access_token'), $params);
    }

    //第三步：刷新access_token（如果需要）
    public function refreshToken($refreshToken){
        $params = [
            'appid' => $this->_config->appKey,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];
        return $this->_request($this->getUrl('sns/oauth2/refresh_token'), $params);
    }

    //第四步：拉取用户信息
    public function getUserInfo($openId, $accessToken){
        $params = [
            'access_token' => $accessToken,
            'openid' => $openId,
            'lang' => 'zh_CN'
        ];

        return $this->_request($this->getUrl('sns/userinfo'), $params);
    }

    //检验授权凭证（access_token）是否有效
    public function checkAccessToken($openId, $accessToken){
        $params = [
            'access_token' => $accessToken,
            'openid' => $openId,
        ];

        $ret = $this->_request($this->getUrl('sns/userinfo'), $params);

        return (isset($ret['errmsg']) && $ret['errmsg'] == 'ok');
    }
    */

}