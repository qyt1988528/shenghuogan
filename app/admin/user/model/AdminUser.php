<?php

namespace Admin\User\Model;

use MDK\Model;
use Phalcon\Mvc\Model\Behavior\Timestampable;

class AdminUser extends Model
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
     * @Column(type="string", size="255", nullable=false, column="username")
     */
    public $username;

    /**
     * 
     * @Column(type="string", size="255", nullable=false, column="password")
     */
    public $password;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="cnname")
     */
    public $cnname;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="enname")
     */
    public $enname;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="avatar")
     */
    public $avatar;

    /**
     * 
     * @Column(type="integer", size="1", nullable=false, column="active")
     */
    public $active;

    /**
     * 
     * @Column(type="string", nullable=true, column="created_time")
     */
    public $created_time;

    /**
     * 
     * @Column(type="string", nullable=true, column="last_login_time")
     */
    public $last_login_time;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="token")
     */
    public $token;

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
     * @return AdminUser[]|AdminUser
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return AdminUser
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
