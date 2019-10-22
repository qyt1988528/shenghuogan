<?php
namespace Maiyuan\Service\Magento\Modules;

class Category extends Module
{
    public function list(array $condition)
    {
        return $this->client->get('category',$condition);
    }

    public function load($id)
    {
        return $this->client->get('category',['entity_id'=>$id]);
    }
}