<?php

namespace Express\Model;

use MDK\Model;

class FaceppDetectImages extends Model
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
     * @Column(type="string", size="255", nullable=true, column="image_url")
     */
    public $image_url;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="blur")
     */
    public $blur;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="face_num")
     */
    public $face_num;
    /**
     *
     * @Column(type="string", nullable=true, column="facepp_result")
     */
    public $facepp_result;

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
