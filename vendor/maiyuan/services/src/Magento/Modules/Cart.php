<?php
namespace Maiyuan\Service\Magento\Modules;


class Cart extends Module
{
    /**
     * get all cart item
     * @return mixed
     */
    public function getItems()
    {
        return $this->client->get('cart/item',[]);
    }

    /**
     * add product to cart
     * @param $product   产品id
     * @param $qty       购买数量
     * @param $options   尺寸参数
     * @param $param     定制化参数
     * [
     *      'product'=>
     *      'qty'=>
     *      'options'=>[
     *          "178" => "56"
     *      ]
     * ]
     * @return mixed
     */
    public function addItem($itemId,$param = [])
    {
        $condition = [
            'product' => $itemId,
        ];
        $condition = array_merge($condition,$param);
        return $this->client->post('cart/item',$condition);
    }

    /**
     * @param $condition
     *  [[
     *      'product'=>
     *      'qty'=>
     *      'options'=>[
     *          "178" => "56"
     *      ]
     * ]]
     * @return mixed
     */
    public function batchAddItem($condition)
    {
        return $this->client->post('cart/item',$condition);
    }

    /**
     * get current user cart item count
     * @return mixed
     */
    public function getItemCount()
    {
        return $this->client->get('cart/item/count',[]);
    }

    /**
     * update cart item (qty or options)
     * @param $itemId
     * @param null $qty
     * @param array $options
     * @return mixed
     */
    public function updateItem($itemId,$qty=null,$options=[])
    {
        $condition = [
            'id' => $itemId,
            'qty' => $qty,
            'options' => $options,
        ];
        return $this->client->put('cart/item',$condition);
    }

    /**
     * delete cart item
     * @param $condition
     * @return mixed
     * @throws \Maiyuan\Service\Exception
     */
    public function deleteItem($condition)
    {
        if(is_string($condition) || is_int($condition) ){
            $condition = [$condition];
        }
        if(!is_array($condition)){
            throw new \Maiyuan\Service\Exception('invalid argument condition need array or int');
        }
        return $this->client->delete('cart/item',$condition);
    }

    /**
     * delete cart all item
     * @return mixed
     */
    public function deleteAllItem()
    {
        return $this->client->post('cart/item/clear',[]);
    }

    /**
     * get cart total include subtotal grand total discount total
     * @return mixed
     */
    public function getTotal()
    {
        return $this->client->get('cart/total',[]);
    }

    /**
     * get current cart coupon
     * @return mixed
     */
    public function getCoupon()
    {
        return $this->client->get('cart/coupon',[]);
    }

    /**
     * apply coupon to cart
     * @param $code
     * @return mixed
     */
    public function applyCoupon($code)
    {
        $condition = [
            "code" => $code
        ];
        return $this->client->post('cart/coupon',$condition);
    }

    /**
     * cancel coupon from cart
     * @param $code
     * @return mixed
     */
    public function cancelCoupon($code)
    {
        $condition = [
            "code" => $code
        ];
        return $this->client->delete('cart/coupon',$condition);
    }

    /**
     * get all available coupon code for current cart
     * @return mixed
     */
    public function couponList()
    {
        return $this->client->get('cart/coupon/list',[]);
    }

    /**
     * 验证产品是否可以加车（活动订单使用）
     * @param $itemId
     * @param array $param
     * @return mixed
     */
    public function productCanAddCart($itemId,$param = [])
    {
        $condition = [
            'product' => $itemId,
        ];
        $condition = array_merge($condition,$param);
        return $this->client->post('cart/item/validate',$condition);
    }
}