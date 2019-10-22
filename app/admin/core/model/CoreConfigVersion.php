<?php

namespace Admin\Core\Model;

use MDK\Model;

class CoreConfigVersion extends Model
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
     * @Column(type="string", size="255", nullable=true, column="store")
     */
    public $store;

    /**
     * 
     * @Column(type="string", size="255", nullable=false, column="system_type")
     */
    public $system_type;

    /**
     * 
     * @Column(type="string", size="255", nullable=false, column="version")
     */
    public $version;

    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="force")
     */
    public $force;

    /**
     * 
     * @Column(type="text", size="0", nullable=true, column="description")
     */
    public $description;

    /**
     * 
     * @Column(type="string", size="0", nullable=true, column="image")
     */
    public $image;


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
     * @return CoreConfigVersion[]|CoreConfigVersion
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return CoreConfigVersion
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
