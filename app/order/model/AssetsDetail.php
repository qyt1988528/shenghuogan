<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/4/29
 * Time: 下午2:06
 */
namespace Order\Model;

use MDK\Model;

class AssetsDetail extends Model
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
     * @Column(type="string", size="60", nullable=true, column="action")
     */
    public $action;
    /**
     *
     * @Column(type="string", size="120", nullable=true, column="action_detail")
     */
    public $action_detail;
    /**
     *
     * @Column(type="string", size="50", nullable=true, column="platform_order_no")
     */
    public $platform_order_no;
    /**
     *
     * @Column(type="string", size="50", nullable=true, column="wechat_order_no")
     */
    public $wechat_order_no;
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
