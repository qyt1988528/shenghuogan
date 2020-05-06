<?php

namespace Parttimejob\Model;

use MDK\Model;

class CertificationRecord extends Model
{

    /**
     * 
     * @Primary
     * @Identity
     * @Column(type="integer", size="20", nullable=false, column="id")
     */
    public $id;

    /**
     * 
     * @Column(type="integer", size="11", nullable=true, column="upload_user_id")
     */
    public $upload_user_id;

    /**
     * 
     * @Column(type="string", nullable=true, column="cellphone")
     */
    public $cellphone;
    /**
     *
     * @Column(type="string", nullable=true, column="id_photo")
     */
    public $id_photo;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="certification_status")
     */
    public $certification_status;
    /**
     * 
     * @Column(type="integer", size="11", nullable=true, column="audit_user_id")
     */
    public $audit_user_id;
    /**
     *
     * @Column(type="string", nullable=true, column="audit_time")
     */
    public $audit_time;
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
