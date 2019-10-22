<?php
namespace Maiyuan\Service\Magento\Modules;

use Maiyuan\Service\Exception;
class Faq extends Module
{
    /**
     * 获取faq列表
     * @param $id
     * @return mixed
     */
    public function list($id,$limit = 10,$page = 1)
    {
        $condition = [
            'id' => $id,
            'limit' => $limit,
            'page' => $page
        ];
        return $this->client->get('faq/list',$condition);
    }

    /**
     * 获取分类列表
     * @return mixed
     */
    public function category($limit = 10,$page = 1)
    {
        $condition = [
            'limit' => $limit,
            'page' => $page
        ];
        return $this->client->get('faq/type',$condition);
    }

    /**
     * 搜索faq
     * @param $keyword
     * @return mixed
     */
    public function search($keyword,$limit = 10,$page = 1)
    {
        $condition = [
            'q' => $keyword,
            'limit' => $limit,
            'page' => $page
        ];
        return $this->client->get('faq/search',$condition);
    }

    /**
     * 查看faq详情
     * @param $id
     * @return mixed
     */
    public function view($id)
    {
        $condition = [
            'id' => $id
        ];
        return $this->client->get('faq',$condition);
    }
}