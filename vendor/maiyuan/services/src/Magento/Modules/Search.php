<?php
namespace Maiyuan\Service\Magento\Modules;


class Search extends Module
{
    public function search($condition)
    {
        // $condition = [
        //     'q'=>$name,
        //     'limit' => $limit,
        //     'page' => $page
        // ];
        return $this->client->get('search',$condition);
    }

    /**
     * search suggest
     * @param $name
     * @return mixed
     */
    public function suggest($name)
    {
        $condition = [
            'q' => $name
        ];
        return $this->client->get('search/suggest',$condition);
    }
}