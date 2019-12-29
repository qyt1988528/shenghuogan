<?php

namespace Express\Model;

use MDK\Model;

class Express extends Model
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
     * @Column(type="integer", nullable=true, column="type_id")
     */
    public $type_id;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="description")
     */
    public $description;
    /**
     *
     * @Column(type="float", nullable=true, column="gratuity")
     */
    public $gratuity;
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
