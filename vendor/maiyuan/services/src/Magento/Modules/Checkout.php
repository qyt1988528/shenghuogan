<?php
namespace Maiyuan\Service\Magento\Modules;


class Checkout extends Module
{
    /**
     * get current shipping address
     * @return mixed
     */
    public function getShippingAddress()
    {
        return $this->client->get('checkout/address/shipping',[]);
    }

    /**
     * change current quote shipping address
     * @param $condition
     * @return mixed
     */
    public function setShippingAddress($condition)
    {
        //todo add require filter
        return $this->client->post('checkout/address/shipping',$condition);
    }

    /**
     * get current billing address
     * @return mixed
     */
    public function getBillingAddress()
    {
        return $this->client->get('checkout/address/billing',[]);
    }

    /**
     * change current billing address
     * @param $condition
     * @return mixed
     */
    public function setBillingAddress($condition)
    {
        //todo add require filter
        return $this->client->post('checkout/address/billing',$condition);
    }

    /**
     * get current shipping support methods
     * @return mixed
     */
    public function getShippingMethods()
    {
        return $this->client->get('checkout/shipping',[]);
    }

    /**
     * change current quote shipping method
     * @param $method
     * @return mixed
     */
    public function saveShippingMethod($method)
    {
        $condition = [
            "shipping_method" => $method
        ];
        return $this->client->post('checkout/shipping',$condition);
    }

    /**
     * get current quote support payment methods
     * @return mixed
     */
    public function getPaymentMethods($orderId)
    {
        $condition = [];
        if($orderId){
            $condition = [
                'orderId'=>$orderId
            ];
        }
        return $this->client->get('checkout/payment',$condition);
    }

    /**
     * change current payment method
     * @param $method
     * @return mixed
     */
    public function savePaymentMethod($method)
    {
        $condition = [
            "method" => $method
        ];
        return $this->client->post('checkout/payment',$condition);
    }

    /**
     * get current shipping insurance info if active
     * [
     *      'title'=>
     *      'value'=>
     *      'value_label'=>
     *      'description'=>
     * ]
     * @return mixed
     */
    public function getShippingInsurance()
    {
        return $this->client->get('checkout/shipping/insurance',[]);
    }

    /**
     * add shipping insurance to current quote
     * @return mixed
     */
    public function addShippingInsurance()
    {
        return $this->client->post('checkout/shipping/insurance',[]);
    }

    /**
     * remove shipping insurance from current quote
     * @return mixed
     */
    public function removeShippingInsurance()
    {
        return $this->client->delete('checkout/shipping/insurance',[]);
    }
}