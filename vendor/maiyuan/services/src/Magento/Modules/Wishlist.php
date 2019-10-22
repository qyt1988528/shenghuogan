<?php
namespace Maiyuan\Service\Magento\Modules;


class Wishlist extends Module
{
    public function addItem($product)
    {
        if(is_int($product) || is_string($product)){
            $product = [
                'product_id' => $product
            ];
        }
        if(!is_array($product)){
            throw new \Maiyuan\Service\Exception('need array or int,'.gettype($product).' gived');
        }
        $result = $this->client->post('wishlist', $product);
        return $result;
    }
    public function removeItem($itemId)
    {
        if(!(is_int($itemId) || is_string($itemId))){
            throw new \Maiyuan\Service\Exception('need int,'.gettype($itemId).' gived');
        }
        $condition= [
            'wishlist_item_id' => $itemId
        ];
        $result = $this->client->delete('wishlist',$condition);
        return $result;
    }
    public function getItems($condition=[])
    {
        return $this->client->get('wishlist',$condition);
    }
    
}