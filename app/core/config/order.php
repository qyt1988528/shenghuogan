<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/2/15
 * Time: 下午1:40
 */

    //支付类型
    const TYPE_WECHAT = 10;
    const TYPE_ALIPAY = 20;

    //支付渠道
    const CHANNEL_WECHAT_JSAPI = 1010; //微信公众号
    const CHANNEL_WECHAT_APP = 1020; //微信APP
    const CHANNEL_WECHAT_MINI_PRO = 1030; //微信小程序

    const CHANNEL_ALIPAY_WAP = 2010; //支付宝手机网站
    const CHANNEL_ALIPAY_APP = 2020; //支付宝APP
    const CHANNEL_ALIPAY_WEB = 2030; //支付宝网站

    //支付状态
    const PAY_STATUS_WAIT = 1; //待付款
    const PAY_STATUS_INVALID = 2; //已失效
    const PAY_STATUS_CLOSE = 4; //已关闭
    const PAY_STATUS_SUCCESS = 3; //已支付
    const PAY_STATUS_REFUNDING = 5; //退款中
    const PAY_STATUS_REFUNDED = 6; //已退款

    //订单状态
    const ORDER_STATUS_INIT = 1;//初始
    const ORDER_STATUS_FINISH = 2;//完成
    const ORDER_STATUS_CLOSE = 3;//失效
    const ORDER_STATUS_RETREAT = 4;//已退款

    //收货状态
    const SHIPPED_STATUS_WAIT_SEND = 1;//待发货
    const SHIPPED_STATUS_WAIT_RECEIVE = 2;//待收货
    const SHIPPED_STATUS_FINISH_RECEIVE = 3;//已收货(待评价)

    //订单失效时间
    const ORDER_INVALID_TIME = 900;//900秒 15分钟

return [
    'pay_type' => [
        'wechat' => ['code'=>10,'title'=>'微信'],
        'alipay' => ['code'=>20,'title'=>'支付宝'],
    ],
    'pay_channel' => [
        'wechat_jsapi' => ['code'=>1010,'title'=>'微信公众号'],
        'wechat_app' => ['code'=>1020,'title'=>'微信APP'],
        'wechat_mimipro' => ['code'=>1030,'title'=>'微信小程序'],
        'alipay_wap' => ['code'=>2010,'title'=>'支付宝手机网站'],
        'alipay_app' => ['code'=>2020,'title'=>'支付宝APP'],
        'alipay_web' => ['code'=>2030,'title'=>'支付宝网站'],
    ],
    'pay_status' => [
        'wait' => ['code'=>1,'title'=>'待付款'],
        'invalid' => ['code'=>2,'title'=>'已失效'],
        'success' => ['code'=>3,'title'=>'已支付'],
        'close' => ['code'=>4,'title'=>'已关闭'],
        'refunding' => ['code'=>5,'title'=>'退款中'],
        'refunded' => ['code'=>6,'title'=>'已退款'],
    ],
    'shipped_status' => [
        'wait_send' => ['code'=>1,'title'=>'待发货'],
        'wait_receive' => ['code'=>2,'title'=>'待收货'],
        'finish_receive' => ['code'=>3,'title'=>'已收货'],
    ],
    'order_status' => [
        'init' => ['code'=>1,'title'=>'初始'],
        'finish' => ['code'=>2,'title'=>'完成'],
        'close' => ['code'=>3,'title'=>'关闭'],
        'retreat' => ['code'=>4,'title'=>'已退款'],
    ],
    'order_invalid_time' => ['code'=>900,'title'=>'订单失效时间'],
];
