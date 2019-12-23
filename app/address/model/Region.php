<?php

namespace Address\Model;

use MDK\Model;

class Region extends Model
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
     * @Column(type="string", nullable=true, column="name")
     */
    public $name;

    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="pid")
     */
    public $pid;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="sname")
     */
    public $sname;
    /**
     *
     * @Column(type="integer", nullable=true, column="level")
     */
    public $level;
    /**
     *
     * @Column(type="string", nullable=true, column="citycode")
     */
    public $citycode;
    /**
     *
     * @Column(type="string", nullable=true, column="yzcode")
     */
    public $yzcode;
    /**
     *
     * @Column(type="string", nullable=true, column="mername")
     */
    public $mername;
    /**
     *
     * @Column(type="float", nullable=true, column="lng")
     */
    public $lng;
    /**
     *
     * @Column(type="float", nullable=true, column="lat")
     */
    public $lat;
    /**
     *
     * @Column(type="string", nullable=true, column="pinyin")
     */
    public $pinyin;


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
