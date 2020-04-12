<?php

namespace Merchant\Model;

use MDK\Model;

class MerchantOperationLog extends Model
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
     * @Column(type="integer", size="11", nullable=true, column="merchant_id")
     */
    public $merchant_id;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="user_id")
     */
    public $user_id;
    /**
     *
     * @Column(type="string", nullable=true, column="before_data")
     */
    public $before_data;

    /**
     *
     * @Column(type="string", size="100", nullable=true, column="after_data")
     */
    public $after_data;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="action_name")
     */
    public $action_name;

    /**
     *
     * @Column(type="string", nullable=true, column="goods_type")
     */
    public $goods_type;
    /**
     *
     * @Column(type="integer", nullable=true, column="goods_id")
     */
    public $goods_id;
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
