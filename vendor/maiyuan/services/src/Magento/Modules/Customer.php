<?php

namespace Maiyuan\Service\Magento\Modules;

use Maiyuan\Service\Exception;

class Customer extends Module
{
    /**
     * reset customer password
     * @param $currentPassword
     * @param $password
     * {
     *       "code": 2,
     *      "message": "Invalid login or password."
     * }
     * @return mixed
     */
    public function reset($currentPassword,$password)
    {
        $condition = [
            'current_password' => $currentPassword,
            'password' => $password,
        ];
        return $this->client->post('customer/reset',$condition);
    }

    /**
     * update customer
     * @param $condition
     * @return mixed
     */
    public function update($condition)
    {
        return $this->client->put('customer',$condition);
    }

    /**
     *
     * @param int $limit
     * @param int $page
     * @return mixed
     */
    public function getCoupons($limit = 10 ,$page = 1)
    {
        $condition = [
            'limit' => $limit,
            'page' => $page
        ];
        return $this->client->get('customer/coupons',$condition);
    }

    /**
     * add coupons to customer
     * @param string $code
     * @return mixed
     */
    public function addCoupons(string $code)
    {
        $condition = [
            'coupon_code' => $code
        ];
        return $this->client->post('customer/coupons',$condition);
    }

    /**
     * add coupons to customer
     * @param string $codes
     * ['CFS5','DIS5']
     * @return mixed
     */
    public function batchAddCoupons(array $codes)
    {
        return $this->client->post('customer/coupons',$codes);
    }

    /**
     * remove coupons from customer
     * @param $id        customer coupons id
     * @return mixed
     */
    public function removeCoupons($id)
    {
        $condition = [
            'id' => $id
        ];
        return $this->client->delete('customer/coupons',$condition);
    }

    /**
     * get customer address
     * @param array $condition
     * @return mixed
     */
    public function getAddresses($condition = [])
    {
        return $this->client->get('customer/address',$condition);
    }

    /**
     * create new address
     * @param $condition
     * $condition = [
     *      'firstname' =>'',
     *      'lastname' =>'',
     *      'telephone' =>'',
     *      'street' =>'',
     *      'country_id' =>'',
     *      'region' =>'',
     *      'postcode' =>'',
     *      'default_shipping' =>'',
     *      'default_billing' =>'',
     * ]
     * @return mixed
     */
    public function createAddress($condition)
    {
        return $this->client->post('customer/address',$condition);
    }

    /**
     * save address
     * @param $condition
     * @return mixed
     */
    public function saveAddress($condition)
    {
        if(empty($condition['entity_id'])){
            throw new Exception('require param entity_id null' ,2);
        }
        return $this->client->put('customer/address',$condition);
    }

    /**
     * delete address
     * @param $id
     * @return mixed
     */
    public function deleteAddress($id)
    {
        $condition = [
            'entity_id' => $id
        ];
        return $this->client->delete('customer/address',$condition);
    }

    /**
     * get customer information
     * @return mixed
     */
    public function getCustomerInfo()
    {
        return $this->client->get('customer',[]);
    }
}