<?php
namespace Tencent\Api;

use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use Tencent\Model\AiFuzzy;
use Tencent\Model\TencentFilterLog;

class Helper extends Api
{
    private $_client;
    public function __construct() {
        $this->_client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->app->tencent->config->tencent->baseUri,
            // You can set any number of default request options.
            'timeout'  => $this->app->tencent->config->tencent->timeout,
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
     * 增加日期
     * @param $result
     */
    public function logFuzzy($result)
    {
        try{
            $data= $result['data'];
            $data['fuzzy'] = !empty($data['fuzzy'])?1:0;
            if(!empty($result['host'])){
                $data['host'] = $result['host'];
            }else{
                $data['host'] = $this->request->getHeader('origin');
                $data['host'] = parse_url($data['host']);
                $data['host'] = isset($data['host']['host'])?$data['host']['host']:'';
            }
            if(!empty($result['path'])){
                $data['path'] = $result['path'];
            }else{
                $data['path'] = $this->request->getURI();
                $data['path'] = parse_url($data['path'])['path'];
            }
            $model = new AiFuzzy();
            $model->save($data);
        }catch (\Exception $e){
//            var_dump($e);
        }

    }
    /**
     * 增加日期
     * @param $result
     */
    public function logFilter($result)
    {
        try{
            $insertData = [
                'filter_all_data' => json_encode($result),
                'filter' => isset($result['filter_params']['filter']) ? (int)$result['filter_params']['filter'] : 0,
            ];
            $data= [];
            $data['host'] = $this->request->getHeader('origin');
            $data['host'] = parse_url($data['host']);
            $data['host'] = isset($data['host']['host'])?$data['host']['host']:'';
            $data['path'] = $this->request->getURI();
            $data['path'] = parse_url($data['path'])['path'];
            $insertData['host'] = $data['host'];
            $insertData['path'] = $data['path'];
//            $insertData['created_at'] = $insertData['updated_at'] = date('Y-m-d H:i:s');
            $model = new TencentFilterLog();
            $model->create($insertData);
            return $model->id;
        }catch (\Exception $e){
//            var_dump($e);
        }

    }
    /**
     * 判断图片是否模糊
     * @param $image
     * @param $source 用于记录调用模糊接口的来源，如:erp_consumer,记host=erp,path=consumer
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function isFuzzy($image,$source=''){
        return [
            'fuzzy' => false,
            'msg' => ''
        ];
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
        $this->logFuzzy($result);
        $data = [
            'fuzzy' => false,
            'msg' => ''
        ];
        if($result['data']['fuzzy'] == true && $result['data']['confidence'] > 0.2){
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
        $this->logFilter($logData);
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

}