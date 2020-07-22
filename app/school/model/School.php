<?php

namespace School\Model;

use MDK\Model;

class School extends Model
{

    /**
     *
     * @Primary
     * @Identity
     * @Column(type="integer", size="20", nullable=false, column="id")
     */
    public $id;


    /**
     *
     * @Column(type="string", nullable=true, column="name")
     */
    public $name;
    /**
     *
     * @Column(type="string", nullable=true, column="stu_id_num")
     */
    public $stu_id_num;
    /**
     *
     * @Column(type="string", nullable=true, column="id_num")
     */
    public $id_num;
    /**
     *
     * @Column(type="integer", nullable=true, column="goods_amount")
     */
    public $goods_amount;
    /**
     *
     * @Column(type="integer", nullable=true, column="pay_status")
     */
    public $pay_status;
    /**
     *
     * @Column(type="string", nullable=true, column="cellphone")
     */
    public $cellphone;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="user_id")
     */
    public $user_id;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="platform_user_id")
     */
    public $platform_user_id;
    /**
     *
     * @Column(type="string", nullable=true, column="goods_type")
     */
    public $goods_type;

    /**
     *
     * @Column(type="int", nullable=true, column="is_hiring")
     */
    public $is_hiring;
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

