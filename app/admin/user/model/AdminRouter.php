<?php

namespace Admin\User\Model;

use MDK\Model;
use Phalcon\Mvc\Model\Behavior\Timestampable;

class AdminRouter extends Model
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
     * @Column(type="string", size="50", nullable=false, column="path")
     */
    public $path;

    /**
     * 
     * @Column(type="string", size="50", nullable=true, column="redirect")
     */
    public $redirect;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="view")
     */
    public $view;

    /**
     * 
     * @Column(type="string", size="50", nullable=true, column="name")
     */
    public $name;

    /**
     * 
     * @Column(type="string", size="5", nullable=true, column="hidden")
     */
    public $hidden;

    /**
     * 
     * @Column(type="integer", size="10", nullable=false, column="parent_id")
     */
    public $parent_id;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="icon")
     */
    public $icon;

    /**
     * 
     * @Column(type="integer", size="255", nullable=true, column="sort")
     */
    public $sort;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="url")
     */
    public $url;

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
     * @return AdminRouter[]|AdminRouter
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return AdminRouter
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
