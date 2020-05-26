<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/4/30
 * Time: 下午2:40
 */
namespace Merchant\Model;

use MDK\Model;

class MerchantWithdrawApply extends Model
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
     * @Column(type="float", nullable=true, column="withdraw_amount")
     */
    public $withdraw_amount;
    /**
     *
     * @Column(type="integer", size="4", nullable=true, column="apply_status")
     */
    public $apply_status;

    /**
     *
     * @Column(type="string", size="60", nullable=true, column="wechat_order_no")
     */
    public $wechat_order_no;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="merchant_payment_code_id")
     */
    public $merchant_payment_code_id;
    /**
     *
     * @Column(type="string", size="255", nullable=true, column="remarks")
     */
    public $remarks;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="apply_user_id")
     */
    public $apply_user_id;

    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="apply_merchant_id")
     */
    public $apply_merchant_id;
    /**
     *
     * @Column(type="integer", size="11", nullable=true, column="operate_user_id")
     */
    public $operate_user_id;
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