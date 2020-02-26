<?php

namespace Address\Model;

use MDK\Model;

class Address extends Model
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
     * @Column(type="integer", size="11", nullable=true, column="user_id")
     */
    public $user_id;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="merchant_id")
     */
    public $merchant_id;

    /**
     *
     * @Column(type="string", size="45", nullable=true, column="name")
     */
    public $name;
    /**
     *
     * @Column(type="string", nullable=true, column="cellphone")
     */
    public $cellphone;
    /**
     *
     * @Column(type="integer", nullable=true, column="province_id")
     */
    public $province_id;
    /**
     *
     * @Column(type="integer", nullable=true, column="city_id")
     */
    public $city_id;
    /**
     *
     * @Column(type="integer", nullable=true, column="county_id")
     */
    public $county_id;
    /**
     *
     * @Column(type="string", nullable=true, column="detailed_address")
     */
    public $detailed_address;
    /**
     *
     * @Column(type="integer", nullable=true, column="is_default")
     */
    public $is_default;
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
