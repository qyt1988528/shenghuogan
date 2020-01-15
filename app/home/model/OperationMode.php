<?php

namespace Home\Model;

use MDK\Model;

class OperationMode extends Model
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
     * @Column(type="string", size="100", nullable=true, column="name")
     */
    public $name;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="image")
     */
    public $image;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="base_url")
     */
    public $base_url;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="is_show")
     */
    public $is_show;
    /**
     *
     * @Column(type="integer", size="5", nullable=true, column="sort")
     */
    public $sort;

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
