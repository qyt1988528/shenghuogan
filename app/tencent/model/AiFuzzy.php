<?php

namespace Tencent\Model;

use MDK\Model;

class AiFuzzy extends Model
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
     * @Column(type="string", nullable=true, column="fuzzy")
     */
    public $fuzzy;

    /**
     *
     * @Column(type="string", nullable=true, column="confidence")
     */
    public $confidence;

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
