<?php

namespace Admin\Core\Model;

use MDK\Model;

class CoreConfigData extends Model
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
     * @Column(type="string", size="50", nullable=true, column="store")
     */
    public $store;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="path")
     */
    public $path;

    /**
     * 
     * @Column(type="string", nullable=true, column="value")
     */
    public $value;

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
     * @return CoreConfigData[]|CoreConfigData
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return CoreConfigData
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
