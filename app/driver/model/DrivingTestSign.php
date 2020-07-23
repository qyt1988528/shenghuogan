<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/7/23
 * Time: 下午3:28
 */
namespace Driver\Model;

use MDK\Model;

class DrivingTestSign extends Model
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
     * @Column(type="integer", size="11", nullable=true, column="driving_test_id")
     */
    public $driving_test_id;
    /**
     *
     * @Column(type="string", nullable=true, column="name")
     */
    public $name;
    /**
     *
     * @Column(type="string", size="100", nullable=true, column="cellphone")
     */
    public $cellphone;

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