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
            'base_uri' => $this->app->tencent->config->wechat->baseUri,
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
            'app_id' => $this->app->tencent->config->tencent->appId,
            'time_stamp' => strval(time()),
            'nonce_str' => strval(rand()),
            'sign' => '',
        ];
        $data = array_merge($data, $params);
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
        if (($start = strpos($image, ",")) !== false) {
            $start++;
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
        if ($start = strpos($image, ",") === false) {
            $image = 'data:image/jpeg;base64,' . $image;
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


    public function getSessionByCode($jsCode)
    {
        $params = [
            'appid' => $this->app->tencent->config->wechat->appId,
            'secret' => $this->app->tencent->config->wechat->appSecret,
            'js_code' => $jsCode,
            'grant_type' => 'authorization_code',
        ];
        $uri = 'sns/jscode2session';
        $url = $this->app->tencent->config->wechat->baseUri . $uri . '?' . http_build_query($params);

        $sessionInfo = $this->app->core->api->Image()->imageFileGetContents($url);
        //wx.login({success (res){console.log(res)}});
        // $json = '{"session_key":"jHwS8qG\/Gwn40YJ6Zhevwg==","openid":"oSbAs5NzFZyCiez1WZNm3JkjCeH4"}';
        // $json = '{"errcode":40029,"errmsg":"invalid code, hints: [ req_id: ihlEY24ce-GYdrra ]"}';
        if (empty($sessionInfo) || (isset($sessionInfo['errcode']) && $sessionInfo['errcode'] != 0)) {
            return [];
        } else {
            $sessionInfo = json_decode($sessionInfo, true);
            if (isset($sessionInfo['session_key']) && isset($sessionInfo['openid'])) {
                $this->app->tencent->api->UserApi()->getInfoByOpenid($sessionInfo['openid'], $sessionInfo['session_key']);
                return $sessionInfo;
            } else {
                return [];
            }
        }
    }


    public function pay($orderId)
    {
        //查询订单
        if (empty($orderId)) {

        }
        $condition = "order_id = " . $orderId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $orderData = $this->_orderModel->findFirst($condition);
        if (empty($orderData)) {
            //订单不存在

        }
        $orderNo = $orderData->order_no ?? '';
        $currentTime = time();
        if (isset($orderData->order_invalid_time) && $currentTime > $orderData->order_invalid_time) {
            //订单失效

        }
        //判断15分钟内是否有商品变动(库存、价格变动、商品下架)
        $condition = "order_id = " . $orderId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $orderGoods = $this->_orderGoodsModel->find($condition);
        if (empty($orderGoods)) {

        }
        $goodsTypes = $this->_config['goods_types'];
        foreach ($orderGoods as $ogv) {
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


}