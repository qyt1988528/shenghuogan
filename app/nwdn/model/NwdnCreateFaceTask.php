<?php

namespace Nwdn\Model;

use MDK\Model;

class NwdnCreateFaceTask extends Model
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
     * @Column(type="string", size="255", nullable=true, column="ethnicity")
     */
    public $ethnicity;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="sku")
     */
    public $sku;
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
     * @Column(type="string", size="255", nullable=true, column="emid")
     */
    public $emid;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="input_url")
     */
    public $input_url;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="background_url")
     */
    public $background_url;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="output_url")
     */
    public $output_url;
    /**
     *
     * @Column(type="string", size="50", nullable=true, column="taskid")
     */
    public $taskid;
    /**
     *
     * @Column(type="integer", size="5", nullable=true, column="phase")
     */
    public $phase;
    /**
     *
     * @Column(type="string", nullable=true, column="create_task_data")
     */
    public $create_task_data;
    /**
     *
     * @Column(type="string", nullable=true, column="update_task_data")
     */
    public $update_task_data;
    /**
     *
     * @Column(type="string", nullable=true, column="created_at")
     */
    public $created_at;
    /**
     *
     * @Column(type="string", nullable=true, column="updated_at")
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
