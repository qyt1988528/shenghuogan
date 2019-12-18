<?php

namespace Lostfound\Model;

use MDK\Model;

class Lostfound extends Model
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
     * @Column(type="string", nullable=true, column="img_url")
     */
    public $img_url;

    /**
     *
     * @Column(type="string", size="255", nullable=true, column="location")
     */
    public $location;
    /**
     *
     * @Column(type="integer", nullable=true, column="stock")
     */
    public $stock;
    /**
     *
     * @Column(type="integer", nullable=true, column="is_selling")
     */
    public $is_selling;
    /**
     *
     * @Column(type="string", size="20", nullable=true, column="cellphone")
     */
    public $cellphone;
    /**
     *
     * @Column(type="string", size="20", nullable=true, column="qq")
     */
    public $qq;
    /**
     *
     * @Column(type="string", size="30", nullable=true, column="wechat")
     */
    public $wechat;
    /**
     *
     * @Column(type="string", nullable=true, column="publish_time")
     */
    public $publish_time;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="description")
     */
    public $description;

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
