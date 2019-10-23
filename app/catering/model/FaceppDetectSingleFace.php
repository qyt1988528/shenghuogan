<?php

namespace Catering\Model;

use MDK\Model;

class FaceppDetectSingleFace extends Model
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
     * @Column(type="integer", size="11", nullable=true, column="images_id")
     */
    public $images_id;

    /**
     * 
     * @Column(type="integer", size="4", nullable=true, column="gender")
     */
    public $gender;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="age")
     */
    public $age;
    /**
     *
     * @Column(type="string", size="20", nullable=true, column="emotion")
     */
    public $emotion;
    /**
     * 
     * @Column(type="string", size="20", nullable=true, column="ethnicity")
     */
    public $ethnicity;
    /**
     *
     * @Column(type="float", size="20", nullable=true, column="facequality")
     */
    public $facequality;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="blur")
     */
    public $blur;
    /**
     *
     * @Column(type="float", size="255", nullable=true, column="headpose_pitch_angle")
     */
    public $headpose_pitch_angle;
    /**
     *
     * @Column(type="float", size="255", nullable=true, column="headpose_roll_angle")
     */
    public $headpose_roll_angle;
    /**
     *
     * @Column(type="float", size="255", nullable=true, column="headpose_yaw_angle")
     */
    public $headpose_yaw_angle;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="face_rectangle")
     */
    public $face_rectangle;
    /**
     *
     * @Column(type="integer", size="5", nullable=true, column="face_rectangle_top")
     */
    public $face_rectangle_top;
    /**
     *
     * @Column(type="integer", size="5", nullable=true, column="face_rectangle_left")
     */
    public $face_rectangle_left;
    /**
     *
     * @Column(type="integer", size="5", nullable=true, column="face_rectangle_width")
     */
    public $face_rectangle_width;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="face_rectangle_height")
     */
    public $face_rectangle_height;
    /**
     *
     * @Column(type="string", nullable=true, column="init_face_data")
     */
    public $init_face_data;

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
