<?php

namespace Order\Model;

use MDK\Model;

class OrderGoods extends Model
{
    /**
     *
     * @Primary
     * @Identity
     * @Column(type="integer", size="20", nullable=false, column="order_goods_id")
     */
    public $order_goods_id;
// `order_goods_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="order_id")
     */
    public $order_id;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="merchant_id")
     */
    public $merchant_id;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="user_id")
     */
    public $user_id;
// `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="goods_id")
     */
    public $goods_id;
// `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品的的id',
    /**
     *
     * @Column(type="string", nullable=true, column="goods_name")
     */
    public $goods_name;
// `goods_name` varchar(100) NOT NULL DEFAULT '' COMMENT '商品的名称',
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="goods_num")
     */
    public $goods_num;
// `goods_num` int(11) NOT NULL DEFAULT '0' COMMENT '商品数量',
    /**
     *
     * @Column(type="float", size="20", nullable=true, column="goods_amount")
     */
    public $goods_amount;
// `goods_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品原价',
    /**
     *
     * @Column(type="float", size="20", nullable=true, column="goods_cost_amount")
     */
    public $goods_cost_amount;
// `goods_cost_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品成本价',
    /**
     *
     * @Column(type="float", size="20", nullable=true, column="goods_current_amount")
     */
    public $goods_current_amount;
// `goods_current_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品现价-成交价',
    /**
     *
     * @Column(type="string", nullable=true, column="goods_type")
     */
    public $goods_type;
// `goods_type_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品类型ID',
    /**
     *
     * @Column(type="string", nullable=true, column="goods_attr")
     */
    public $goods_attr;
// `goods_attr` varchar(200) NOT NULL DEFAULT '' COMMENT '商品规格',
    /**
     *
     * @Column(type="string", nullable=true, column="goods_cover")
     */
    public $goods_cover;
// `goods_cover` varchar(200) NOT NULL DEFAULT '' COMMENT '商品图片',
    /**
     *
     * @Column(type="string", nullable=true, column="goods_detail_data")
     */
    public $goods_detail_data;
// `goods_detail_data` text CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '商品详情快照',
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
