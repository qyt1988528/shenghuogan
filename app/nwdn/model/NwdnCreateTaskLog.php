<?php

namespace Nwdn\Model;

use MDK\Model;

class NwdnCreateTaskLog extends Model
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
     * @Column(type="string", size="255", nullable=true, column="image_link")
     */
    public $image_link;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="image_md5")
     */
    public $image_md5;
    /**
     * 
     * @Column(type="string", size="255", nullable=true, column="source_name")
     */
    public $source_name;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="task_id")
     */
    public $task_id;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="state")
     */
    public $state;
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
