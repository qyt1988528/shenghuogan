<?php

namespace Express\Model;

use MDK\Model;

class ExpressSend extends Model
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
     * @Column(type="integer", nullable=true, column="express_company_id")
     */
    public $express_company_id;


    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="user_address_id")
     */
    public $user_address_id;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="address_id")
     */
    public $address_id;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="remarks")
     */
    public $remarks;
    /**
     *
     * @Column(type="float", nullable=true, column="gratuity")
     */
    public $gratuity;
    /**
     *
     * @Column(type="int", nullable=true, column="is_hiring")
     */
    public $is_hiring;
    /**
     *
     * @Column(type="int", nullable=true, column="publish_user_id")
     */
    public $publish_user_id;
    /**
     *
     * @Column(type="time", nullable=true, column="publish_time")
     */
    public $publish_time;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="merchant_id")
     */
    public $merchant_id;
    /**
     *
     * @Column(type="string", nullable=true, column="goods_type")
     */
    public $goods_type;
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
