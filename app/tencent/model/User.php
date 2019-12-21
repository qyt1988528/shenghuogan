<?php

namespace Tencent\Model;

use MDK\Model;

class User extends Model
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
     * @Column(type="string", size="255", nullable=true, column="name")
     */
    public $name;

    /**
     * 
     * @Column(type="string", nullable=true, column="password")
     */
    public $password;

    /**
     * 
     * @Column(type="string", nullable=true, column="merchant_id")
     */
    public $merchant_id;

    /**
     *
     * @Column(type="string", nullable=true, column="openid")
     */
    public $openid;

    /**
     *
     * @Column(type="string", nullable=true, column="access_token")
     */
    public $access_token;
    /**
     *
     * @Column(type="string", nullable=true, column="session_key")
     */
    public $session_key;
    /**
     *
     * @Column(type="string", nullable=true, column="session_key_time")
     */
    public $session_key_time;


    /**
     *
     * @Column(type="string", nullable=true, column="cellphone")
     */
    public $cellphone;

    /**
     *
     * @Column(type="string", nullable=true, column="nickname")
     */
    public $nickname;

    /**
     *
     * @Column(type="string", nullable=true, column="gender")
     */
    public $gender;
    /**
     *
     * @Column(type="string", nullable=true, column="avatar_url")
     */
    public $avatar_url;
    /**
     *
     * @Column(type="string", nullable=true, column="country")
     */
    public $country;
    /**
     *
     * @Column(type="string", nullable=true, column="province")
     */
    public $province;
    /**
     *
     * @Column(type="string", nullable=true, column="city")
     */
    public $city;
    /**
     *
     * @Column(type="integer", nullable=true, column="key_time")
     */
    public $key_time;
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
