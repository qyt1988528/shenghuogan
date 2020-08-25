<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/2/25
 * Time: 下午2:13
 */

namespace Tencent\Api;

use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use Order\Model\Order;
use Order\Model\OrderDetail;
use Order\Model\OrderGoods;

class Pay extends Api
{
    private $_client;

    private $_config;
    private $_order;
    private $_model;
    private $_orderModel;
    private $_orderDetailModel;
    private $_orderGoodsModel;
    private $_invalid_time;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_order = $this->app->core->config->order->toArray();
        $this->_orderModel = new Order();
        $this->_orderDetailModel = new OrderDetail();
        $this->_orderGoodsModel = new OrderGoods();
        $this->_invalid_time = 1800;//30分钟
        $this->_client = new Client([
            'base_uri' => $this->app->tencent->config->wechat->baseMchUri,
            // 'base_uri' => $this->app->tencent->config->wechat->baseUri,
            'timeout' => $this->app->tencent->config->wechat->timeout,
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
        return function ($response) {
            // var_dump($response);exit;
            if ($response->getStatusCode() != 200) {
                throw new \Exception("NetWork Error :get response code " . $response->getStatusCode(), 1);
            }
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                throw new \Exception($result['errmsg'], 1);
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
        foreach ($params as $key => $value) {
            if ($value !== '') {
                $str .= $key . '=' . urlencode($value) . '&';
            }
        }

        // 3. 拼接app_key
        $str .= 'key=' . $this->app->tencent->config->wechat->appSecret;
        // $str .= 'app_key=' . $this->app->tencent->config->wechat->appKey;

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
            'app_id' => $this->app->tencent->config->wechat->appId,
            'time_stamp' => strval(time()),
            'nonce_str' => strval(rand()),
            'sign' => '',
        ];
        $data = array_merge($data, $params);
        $data['sign'] = $this->_sign($data);
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

    public function testParams($orderNo){

        $params = [
            'appid' => $this->app->tencent->config->wechat->appId,//小程序ID
            'mch_id' => $this->app->tencent->config->wechat->mchId,//商户号
            'nonce_str' => md5($orderNo),//随机字符串
            'sign' => '',//签名
            'body' => '测试支付',//商品描述
            'out_trade_no' => $orderNo,//商户订单号
            'total_fee' => 1,//标价金额
            'spbill_create_ip' => '192.168.1.123',//终端IP
            'notify_url' => 'http://www.weixin.qq.com/wxpay/pay.php',//回调地址
            'trade_type' => 'JSAPI',//交易类型
        ];
        $params['sign'] = $this->_sign($params);
        return $params;
    }
    public function testPay($orderNo){
        $uri = $this->app->tencent->config->wechat->payUri;
        $params = $this->testParams($orderNo);
        /*
        */
        $baseUrl = $this->app->tencent->config->wechat->baseMchUri;
        $url = $baseUrl.ltrim($uri,'/');
        $xmlRequestData = $this->arrayToXml($params);
        $data = $this->wxpost($url,$xmlRequestData);
        var_dump($data);
        $data = $this->xmlToArray($data);
        var_dump($data);
        exit;
        $response = $this->_client->request('POST', $uri,[
            "json" => $params,
            "headers" => $this->_getHeader(),
        ]);
        $result = $response->getBody();
        var_dump('----------1-----------');
        var_dump($result);
        $result = json_decode($result,true);
        var_dump('----------2-----------');
        var_dump($result);exit;
        $data = $result['data'] ?? [];

        return $data;
    }

    public function pay($orderId)
    {
        //查询订单
        if (empty($orderId)) {
            throw new \Exception('订单不存在', 10001);

        }
        $condition = "order_id = " . $orderId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $orderData = $this->_orderModel->findFirst($condition);
        if (empty($orderData)) {
            //订单不存在
            throw new \Exception('订单不存在', 10002);

        }
        $orderNo = $orderData->order_no ?? '';
        $orderAmount = $orderData->order_amount ?? '';
        $currentTime = time();
        if (isset($orderData->order_invalid_time) && $currentTime > $orderData->order_invalid_time) {
            //订单失效
            throw new \Exception('订单已失效，请重新下单', 10003);
        }
        //判断15分钟内是否有商品变动(库存、价格变动、商品下架)
        $condition = "order_id = " . $orderId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $orderGoods = $this->_orderGoodsModel->find($condition);
        if (empty($orderGoods)) {
            throw new \Exception('订单已失效，请重新下单', 10004);
        }
        $merchantId = 0;
        $goodsTypes = $this->_config['goods_types'];
        foreach ($orderGoods as $ogv) {
            $merchantId = $ogv->merchant_id;
            $goodsType = $ogv->goods_type;
            $goodsId = $ogv->goods_id;
            $goods = $this->modelsManager->createBuilder()
                ->columns('*')
                // ->columns('id,stock,title,img_url,original_price,self_price,description,location,is_recommend,sort,base_fav_count,base_order_count')
                ->from(['sg' => $goodsTypes[$goodsType]['model']])
                ->where('sg.id = :goods_id: ', ['goods_id' => $goodsId])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->limit(1)
                ->getQuery()
                // ->execute();
                ->getSingleResult();

            $desc = $goodsTypes[$goodsType]['desc'];
            if (empty($goods)) {
                throw new \Exception('商品变化，请重新下单', 10005);
            }
            if (isset($goods->is_selling)) {
                //是否在售(美食、驾考、酒店、失物招领、租车、二手物品、超市、门票)
                if ($goods->is_selling != $this->_config['selling_status']['selling']) {
                    throw new \Exception($desc . '已下架', 1004);

                }
                //库存是否满足要求
                if ($goods->stock < $ogv->goods_num) {
                    throw new \Exception($desc . '库存不足', 1005);
                }

                if($goods->self_price != $ogv->goods_current_amount){
                    throw new \Exception($desc . '价格已变动', 1006);
                }

            }

            if (isset($goods->is_renting)) {
                //是否出租(租房)
                if ($goods->is_renting != $this->_config['renting_status']['renting']) {
                    throw new \Exception($desc . '已下架', 1004);
                }
                if($goods->self_price != $ogv->goods_current_amount){
                    throw new \Exception($desc . '价格已变动', 1006);
                }
            }
            if (isset($goods->is_hiring)) {
                //是否在招人(代发快递、代取快递、兼职)
                if ($goods->is_hiring != $this->_config['hiring_status']['hiring']) {
                    throw new \Exception($desc . '已下架', 1004);
                }
                if($goods->total_price != $ogv->goods_current_amount){
                    throw new \Exception($desc . '价格已变动', 1006);
                }
            }

        }




        //调微信支付

        //根据返回结果更新状态

        try{
            $updateData = [
                'order_id' => $orderId,
                'pay_channel' => $this->_order['pay_channel']['wechat_mimipro']['code'],
                'pay_time' => time(),
                'pay_status' => $this->_order['pay_status']['success']['code'],
                // 'order_status' => $this->_order['order_status']['finish']['code'],
            ];
            $orderData->update($updateData);
            //加至商户余额
            $this->app->merchant->api->MerchantManage()->updateMerchantBalance($merchantId,$orderAmount);
            return true;
        }catch (\Exception $e){
            throw new \Exception('支付失败，请稍后重试', 10006);
        }
        return true;
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        'appid,mch_id,nonce_str,sign,body,out_trade_no,total_fee,spbill_create_ip,notify_url,trade_type
        	';
        $params = [
            'appid' => $this->app->tencent->config->wechat->appId,//小程序ID
            'mch_id' => $this->app->tencent->config->wechat->mchId,//商户号
            'nonce_str' => md5($orderNo),//随机字符串
            'sign',//签名
            'body',//商品描述
            'out_trade_no' => $orderNo,//商户订单号
            'total_fee',//标价金额
            'spbill_create_ip',//终端IP
            'notify_url',//回调地址
            'trade_type' => 'JSAPI',//交易类型
        ];

    }

        /*
     * @brief       signature
     * @param       array $params
     * @return      string MD5 Result
     * @description 字段名需要从小到大字母序排序；值为空的参数不能参与签名；最后拼接商户支付密钥
     * */
    private function _signature( $params ){
        $params = array_filter($params);//filter '' and null
        ksort($params);//sort by alphabet sequence
        $result = self::_buildSignString($params).'&key='.$this->_apiKey;
        return strtoupper(md5($result));//to upper case after MD5
    }

    /*
     * @brief       build SignatureTempString
     * @param       $params
     * @return      string like key=value&key1=value1&...
     * @description http_build_query会使验签失败，因为会自动转码
     * */
    private static function _buildSignString($params){
        $str  ='';
        foreach($params as $key => $value){
            $str .= '&'.$key.'='.$value;
        }
        return ltrim($str,'&');
    }
    /*
     * @brief        getXmlConfig
     * @return       array $config
     * @description  xmlConfig for Curl Post
     * */
    private static function _getXmlConfig(){
        $header[] = "Content-type: text/xml";
        return array(
            CURLOPT_HTTPHEADER => $header,
        );
    }
    private function _getHeader(){
        return [
           "Content-type" => "text/xml",
        ];

    }


    public function wxpost($url,$post)
    {
        //初始化
        $curl = curl_init();
        $header[] = "Content-type: text/xml";//定义content-type为xml
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
//        curl_setopt($curl, CURLOPT_HEADER, 1);
        //定义请求类型
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        $post_data = $post;
        // $post_data = $this->arrayToXml($post);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求

        //显示获得的数据
//        print_r($data);
        if ($data)
        {
            curl_close($curl);
            return $data;
        }else{
            $res = curl_error($curl);
            curl_close($curl);
            return $res;
        }

    }


//    将数组转化为xml数据格式
    public function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key=>$val){
            if(is_array($val)){
                $xml.="<".$key.">".$this->arrayToXml($val)."</".$key.">";
            }else{
                $xml.="<".$key.">".$val."</".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    //    将XML转化为json/数组
    public function xmlToArray($xml,$type=''){
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
//        simplexml_load_string()解析读取xml数据，然后转成json格式
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($type == "json")
        {
            $json = json_encode($xmlstring);
            return $json;
        }
        $arr = json_decode(json_encode($xmlstring),true);
        return $arr;
    }

    public function queryOrderPay(){
        'https://api.mch.weixin.qq.com/pay/orderquery';
        $data = [
            'appid',
            'mch_id',
            'out_trade_no',
            'nonce_str',
            'sign',
        ];
    }

    public function receiveCallback(){
        $data = [
            'appid',
            'mch_id',
            'device_info',//否
            'nonce_str',
            'sign',
            'sign_type',//
            'result_code',
            'err_code',//
            'err_code_des',//
            'openid',
            'is_subscribe',
            'trade_type',
            'bank_type',
            'total_fee',
            'settlement_total_fee',//
            'fee_type',//
            'cash_fee',
            'cash_fee_type',//
            'coupon_fee',//
            'coupon_count',
            //'coupon_type_',
            //'coupon_id_',
            //'coupon_fee_',
            'transaction_id',
            'out_trade_no',
            'attach',//
            'time_end',
        ];
    }


}