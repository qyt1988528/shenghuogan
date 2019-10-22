<?php

namespace Tencent\Model;

use MDK\Model;

class TencentFilterLog extends Model
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
     * @Column(type="string", size="255", nullable=true, column="host")
     */
    public $host;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="path")
     */
    public $path;

    /**
     * 
     * @Column(type="string", nullable=true, column="filter")
     */
    public $filter;

    /**
     *
     * @Column(type="string", nullable=true, column="filter_all_data")
     */
    public $filter_all_data;
    /**
     *
     * @Column(type="string", column="created_at")
     */
    public $created_at;
    /**
     *
     * @Column(type="string", column="updated_at")
     */
    public $updated_at;
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
