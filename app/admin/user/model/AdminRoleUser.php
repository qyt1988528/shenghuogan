<?php

namespace Admin\User\Model;

use MDK\Model;
use Phalcon\Mvc\Model\Behavior\Timestampable;

class AdminRoleUser extends Model
{
    /**
     *
     * @Primary
     * @Identity
     * @Column(type="integer", size="10", nullable=false, column="id")
     */
    public $id;

    /**
     * @Column(type="integer", size="11", nullable=false, column="role_id")
     */
    public $role_id;

    /**
     * 
     * @Column(type="integer", size="11", nullable=true, column="user_id")
     */
    public $user_id;

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
     * @return AdminRoleUser[]|AdminRoleUser
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return AdminRoleUser
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
