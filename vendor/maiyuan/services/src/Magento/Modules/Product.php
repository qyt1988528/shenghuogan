<?php
namespace Maiyuan\Service\Magento\Modules;

class Product extends Module
{
    public function list($condition)
    {
        return $this->client->get('product',$condition);
    }

    public function load($id)
    {
        $condition =['id' => $id];
        return $this->client->get('product/detail',$condition);
    }

    public function loadBySku($sku)
    {
        $condition =['sku' => $sku];
        return $this->client->get('product/detail',$condition);
    }

    public function wishlist($sku)
    {
        $condition =['sku' => $sku];
        return $this->client->get('product/wishlist',$condition);
    }

    public function dailydeal($condition)
    {
        return $this->client->get('product/dailydeal',$condition);
    }
}