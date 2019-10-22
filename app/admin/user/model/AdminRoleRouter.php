<?php

namespace Admin\User\Model;

use MDK\Model;

class AdminRoleRouter extends Model
{

    /**
     * 
     * @Primary
     * @Identity
     * @Column(type="integer", size="10", nullable=false, column="id")
     */
    public $id;

    /**
     * 
     * @Column(type="integer", size="10", nullable=false, column="role_id")
     */
    public $role_id;

    /**
     * 
     * @Column(type="integer", size="10", nullable=false, column="router_id")
     */
    public $router_id;

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
     * @return AdminRoleRouter[]|AdminRoleRouter
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return AdminRoleRouter
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
