<?php

namespace User\Model;

use MDK\Model;

class Ticket extends Model
{
    /**
     *
     * @Primary
     * @Identity
     * @Column(type="integer", size="11", nullable=false, column="id")
     */
    public $id;

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


    /**
     *
     * @Column(type="string", size="100", nullable=true, column="title")
     */
    public $title;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="title_pinyin")
     */
    public $title_pinyin;
    /**
     *
     * @Column(type="string", nullable=true, column="img_url")
     */
    public $img_url;
    /**
     *
     * @Column(type="float", nullable=true, column="cost_price")
     */
    public $cost_price;
    /**
     *
     * @Column(type="float", nullable=true, column="original_price")
     */
    public $original_price;
    /**
     *
     * @Column(type="float", nullable=true, column="self_price")
     */
    public $self_price;
    /**
     *
     * @Column(type="float", nullable=true, column="together_price")
     */
    public $together_price;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="location")
     */
    public $location;
    /**
     *
     * @Column(type="string", nullable=true, column="description")
     */
    public $description;
    /**
     *
     * @Column(type="integer", size="10", nullable=true, column="stock")
     */
    public $stock;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="is_selling")
     */
    public $is_selling;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="is_recommend")
     */
    public $is_recommend;
    /**
     *
     * @Column(type="integer", size="10", nullable=true, column="sort")
     */
    public $sort;
    /**
     *
     * @Column(type="integer", size="19", nullable=true, column="base_fav_count")
     */
    public $base_fav_count;
    /**
     *
     * @Column(type="integer", size="10", nullable=true, column="base_order_count")
     */
    public $base_order_count;
    /**
     *
     * @Column(type="string", nullable=true, column="goods_type")
     */
    public $goods_type;
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
