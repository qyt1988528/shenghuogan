<?php
namespace Maiyuan\Service\Magento\Modules;


class Order extends Module
{
    /**
     * get order list
     * @return mixed
     */
    public function list($page,$limit,$offset = 0)
    {
        $condition = [
            'page' => $page,
            'limit' => $limit,
        ];
        if($offset){
            $condition['offset'] = $offset;
        }
        return $this->client->get('order',$condition);
    }

    /**
     * create order
     * @return mixed
     */
    public function save($condition)
    {
        return $this->client->post('order',$condition);
    }

    /**
     * cancel order
     * @param $id
     * @return mixed
     */
    public function cancel($id,$reasonTxt = '')
    {
        $condition = ['orderId'=>$id,'reasonTxt'=>$reasonTxt];
        return $this->client->post('order/cancel',$condition);
    }

    /**
     * update order
     * @param $condition
     * @return mixed
     */
    public function update($condition)
    {
        return $this->client->put('order',$condition);
    }

    /**
     * get order payment param
     * @param $id
     * @return mixed
     */
    public function getPaymentParam($id)
    {
        $condition = [
            'id' => $id
        ];
        return $this->client->get('order/payment',$condition);
    }

    /**
     * get order detail
     * @param $condition
     * @return mixed
     */
    public function getDetail($condition)
    {
        return $this->client->get('order/detail',$condition);
    }

    /**
     * get order detail by id
     * @param $condtion
     * @return mixed
     */
    public function getDetailById($id)
    {
        $condition = [
            'orderId' => $id
        ];
        return $this->getDetail($condition);
    }

    /**
     * get order detail by increment id
     * @param $condtion
     * @return mixed
     */
    public function getDetailByIncrementId($incrementId)
    {
        $condition = [
            'incrementId' => $incrementId
        ];
        return $this->getDetail($condition);
    }

    /**
     * tracking order
     * @param $incrementId
     * @return mixed
     */
    public function tracking($incrementId)
    {
        $condition =[
            'incrementId' => $incrementId
        ];
        return $this->client->get('order/tracking',$condition);
    }

    /**
     * change order payment method
     * @param $orderId
     * @param $paymentMehtod
     * @return mixed
     */
    public function changePayment($orderId,$paymentMehtod)
    {
        $condition = [
            "orderId" => $orderId,
            "paymentMethod" => $paymentMehtod
        ];
        return $this->client->put('order/payment',$condition);
    }

    /**
     * reorder
     * when order has out of stock or un salable return product list
     * @param $orderId
     * @param bool $confirm if confirm continue reorder
     * @return mixed
     */
    public function reorder($orderId,$confirm = false)
    {
        $condition = [
            'orderId'=>$orderId,
            'confirm'=>$confirm,
        ];
        return $this->client->post('order/reorder',$condition);
    }

    /**
     * change order address
     * @param $orderId
     * @param $type type of address billing or shipping
     * @param $data address data 
     * @return mixed
     */
    public function saveAddress($orderId,$type,$data)
    {
        $condition = [
            "orderId" => $orderId,
            "type" => $type,
            "address" => $data,
        ];
        return $this->client->put('order/address',$condition);
    }

    /**
     * get order totals
     * @param $params
     * @return mixed
     */
    public function getTotals($params = [])
    {
        return $this->client->get('order/total',$params);
    }


    /**
     * created a bargain order with product address shipping method payment method
     * @example
     * {
    "products":[
    {
    "id":186,
    "price":1,
    "options":{
    "766":49
    }
    }
    ],
    "address":{
    "customer_address_id":"",
    "firstname":"jane",
    "lastname":"Done",
    "city":"haerbin",
    "country_id":"US",
    "region_id":"2",
    "postcode":"201301"
    },
    "shippingMethod":"flatrate0_flatrate0",
    "paymentMethod":"paypal_rest"

    }
     * @param $products
     * @param $address
     * @param $shippingMethod
     * @param $paymantMethod
     * @return mixed
     */
    public function saveBargainOrder($products,$address,$shippingMethod,$paymentMethod)
    {
        $params = [
            "products" => $products,
            "address" => $address,
            "shippingMethod" => $shippingMethod,
            "paymentMethod" => $paymentMethod,
        ];
        return $this->client->post('order/activity',$params);

    }

    /**
     * get shipping method by shipping address
     * @param $address
     * "address":{
    "customer_address_id":"",
    "firstname":"jane",
    "lastname":"Done",
    "city":"haerbin",
    "country_id":"US",
    "region_id":"2",
    "postcode":"201301",
     "street":"street"
    }
     * @return mixed
     */
    public function getBargainShippingMethod($address)
    {
        return $this->client->post('order/activity/shipping',$address);
    }
}