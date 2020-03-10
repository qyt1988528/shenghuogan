<?php

namespace Order\Model;

use MDK\Model;

class Order extends Model
{
    /**
     *
     * @Primary
     * @Identity
     * @Column(type="integer", size="11", nullable=false, column="order_id")
     */
    public $order_id;
// `order_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    /**
     *
     * @Column(type="string", size="50", nullable=true, column="order_no")
     */
    public $order_no;
// `order_no` varchar(50) NOT NULL DEFAULT '' COMMENT '订单编号',
    /**
     *
     * @Column(type="string", size="50", nullable=true, column="serial_no")
     */
    public $serial_no;
// `serial_no` varchar(50) NOT NULL DEFAULT '' COMMENT '支付流水号',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="user_id")
     */
    public $user_id;
// `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户uid',
    /**
     *
     */
// * @Column(type="integer", size="11", nullable=true, column="merchant_id")
//     public $merchant_id;
// `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户uid',
    /**
     *
     * @Column(type="float", nullable=true, column="goods_amount")
     */
    public $goods_amount;
// `goods_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原始总金额',
    /**
     *
     * @Column(type="float", nullable=true, column="order_amount")
     */
    public $order_amount;
// `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单实付总金额',
    /**
     *
     * @Column(type="float", nullable=true, column="coupon_amount")
     */
    public $coupon_amount;
// `coupon_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠券金额',
    /**
     *
     * @Column(type="string", size="50", nullable=true, column="coupon_no")
     */
    public $coupon_no;
// `coupon_no` varchar(50) NOT NULL DEFAULT '' COMMENT '优惠券码',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="pay_channel")
     */
    public $pay_channel;
// `pay_channel` int(6) NOT NULL DEFAULT '0' COMMENT '支付渠道',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="pay_time")
     */
    public $pay_time;
// `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '付款时间',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="pay_status")
     */
    public $pay_status;
// `pay_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:待付款,3已支付',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="order_status")
     */
    public $order_status;
// `order_status` tinyint(4) NOT NULL DEFAULT '10' COMMENT '10:有效,20:失效,30:完成,40:退货',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="order_invalid_time")
     */
    public $order_invalid_time;
// `order_invalid_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单失效时间',
    /**
     *
     * @Column(type="string", size="50", nullable=true, column="form_id")
     */
    public $form_id;
    /**
     *
     * @Column(type="string", size="50", nullable=true, column="is_manual")
     */
    public $is_manual;
// `form_id` varchar(50) NOT NULL DEFAULT '微信form_id',
// `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
// `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
// `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="first_buy")
     */
    public $first_buy;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="add_timestamp")
     */
    public $add_timestamp;
    /**
     *
     * @Column(type="string", nullable=true, column="create_time")
     */
    public $create_time;
    /**
     *
     * @Column(type="string", nullable=true, column="update_time")
     */
    public $update_time;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="status")
     */
    public $status;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return AiFuzzy[]|AiFuzzy
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return AiFuzzy
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }


}
