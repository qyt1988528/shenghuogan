<?php

namespace Supermarket\Model;

use MDK\Model;

class SupermarketGoods extends Model
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
     * @Column(type="string", size="100", nullable=true, column="title")
     */
    public $title;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="img_url")
     */
    public $mg_url;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="type_id")
     */
    public $type_id;
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
     * @Column(type="string", nullable=true, column="together_price")
     */
    public $together_price;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="description")
     */
    public $description;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="specs")
     */
    public $specs;
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
