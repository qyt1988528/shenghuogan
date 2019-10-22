<?php
namespace Maiyuan\Service\Magento\Modules;


class Component extends Module
{
    /**
     * get single rule
     * @param $code
     * @return mixed
     */
    public function getRule($code,$query= [])
    {
        $query['code'] = $code;
        return $this->client->get('component/rule',$query);
    }

    /**
     * get group rule
     * @param $code
     * @return mixed
     */
    public function getGroupRule($code)
    {
        $condition = [
            'group' => $code
        ];
        return $this->client->get('component/rule',$condition);
    }
}