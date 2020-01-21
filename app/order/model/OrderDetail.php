<?php

namespace Order\Model;

use MDK\Model;

class OrderDetail extends Model
{
    /**
     *
     * @Primary
     * @Identity
     * @Column(type="integer", size="20", nullable=false, column="order_detail_id")
     */
    public $order_detail_id;
// `order_detail_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="order_id")
     */
    public $order_id;
// `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="address_id")
     */
    public $address_id;
// `address_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '地址id',
    /**
     *
     * @Column(type="string", size="20", nullable=true, column="receiver")
     */
    public $receiver;
// `receiver` varchar(10) NOT NULL DEFAULT '' COMMENT '收货人的姓名',
    /**
     *
     * @Column(type="string", size="11", nullable=true, column="cellphone")
     */
    public $cellphone;
// `cellphone` varchar(11) NOT NULL DEFAULT '' COMMENT '收货人的手机',
    /**
     *
     * @Column(type="string", size="10", nullable=true, column="province")
     */
    public $province;
// `province` varchar(10) NOT NULL DEFAULT '' COMMENT '收货人的省份',
    /**
     *
     * @Column(type="string", size="10", nullable=true, column="city")
     */
    public $city;
// `city` varchar(10) NOT NULL DEFAULT '' COMMENT '收货人的城市',
    /**
     *
     * @Column(type="string", size="10", nullable=true, column="county")
     */
    public $county;
// `county` varchar(10) NOT NULL DEFAULT '' COMMENT '收货人的地区',
    /**
     *
     * @Column(type="string", nullable=true, column="detailed_address")
     */
    public $detailed_address;
// `detailed_address` varchar(256) NOT NULL DEFAULT '' COMMENT '详细地址',
    /**
     *
     * @Column(type="string", size="20", nullable=true, column="shipping_type")
     */
    public $shipping_type;
// `shipping_type` varchar(20) NOT NULL DEFAULT '' COMMENT '商品配送类型/GCL/Group/Shipping',
    /**
     *
     * @Column(type="string", size="50", nullable=true, column="shipping_sn")
     */
    public $shipping_sn;
// `shipping_sn` varchar(50) NOT NULL DEFAULT '' COMMENT '商品配送单号',
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="delay_confirm")
     */
    public $delay_confirm;
// `delay_confirm` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '48小时后自动确认收货开关',
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="shipping_status")
     */
    public $shipping_status;
// `shipping_status` tinyint(4) NOT NULL DEFAULT '10' COMMENT '商品配送状态/GCL/Group/Shipping',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="order_end_time")
     */
    public $order_end_time;
// `order_end_time` int(11) NOT NULL DEFAULT '0' COMMENT '订单确认收货截止时间',
// `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
// `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
// `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
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
