<?php

namespace Ticket\Api;

use MDK\Api;
use Ticket\Model\Ticket;

class Helper extends Api
{
    private $_config;
    private $_model;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new Ticket();
    }

    public function getInsertFields()
    {
        return $insertFields = [
            'img_url',
            'title',
            'stock',
            'cost_price',
            'original_price',
            'self_price',
            'location',
            'description',
        ];
    }

    public function getDefaultInsertFields($postData)
    {
        $defaultInsertFields = [
            'is_selling' => $this->_config['selling_status']['selling'],
            'base_fav_count' => mt_rand(20, 50),
            'base_order_count' => mt_rand(20, 50),
            'create_time' => date('Y-m-d H:i:s'),
            'user_id' => $postData['user_id'] ?? 0,
            'merchant_id' => $postData['merchant_id'] ?? 0,
        ];
        if (!isset($postData['together_price']) || empty($postData['together_price'])) {
            $defaultInsertFields['together_price'] = $postData['self_price'];
        }
        if (!empty($postData['title'])) {
            $defaultInsertFields['title_pinyin'] = $this->app->core->api->Pinyin()->getpy($postData['title']);
        }
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }

    public function createTicket($postData)
    {
        try {
            $insertData = $this->getDefaultInsertFields($postData);
            foreach ($this->getInsertFields() as $v) {
                $insertData[$v] = $postData[$v];
            }
            $model = $this->_model;
            $model->create($insertData);
            return !empty($model->id) ? $model->id : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function updateTicket($postData)
    {
        try {
            $updateData = ['id' => $postData['id']];
            $updateModel = $this->_model->findFirstById($postData['id']);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($postData['id'],$postData['user_id']);
            if($judgeResult == false){
                return false;
            }
            foreach ($this->getInsertFields() as $v) {
                $updateData[$v] = $postData[$v];
            }
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    //下架
    public function withdrawTicket($ticketId,$userId)
    {
        try {
            $updateModel = $this->_model->findFirstById($ticketId);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($ticketId,$userId);
            if($judgeResult == false){
                return false;
            }
            $updateData = [
                'id' => $ticketId,
                'is_selling' => $this->_config['selling_status']['unselling'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    //上架
    public function unwithdrawTicket($ticketId,$userId)
    {
        try {
            $updateModel = $this->_model->findFirstById($ticketId);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($ticketId,$userId);
            if($judgeResult == false){
                return false;
            }
            $updateData = [
                'id' => $ticketId,
                'is_selling' => $this->_config['selling_status']['selling'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteTicket($ticketId,$userId)
    {
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($ticketId);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($ticketId,$userId);
            if($judgeResult == false){
                return false;
            }
            $updateData = [
                'id' => $ticketId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($ticketId,$userId=0)
    {
        $condition = "id = " . $ticketId;
        $judgeResult = $this->judgeUser($ticketId,$userId);
        if($judgeResult == false){
            $condition .= " and is_selling = " . $this->_config['selling_status']['selling'];
            return false;
        }
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }

    public function search($goodsName)
    {
        /*
        $slideAds = $this->modelsManager->createBuilder()
            ->columns('cts.image_ratio,cts.image,cts.jump_url,cts.title,cts.ga_name,vc.condition')
            ->from(['cts'=>'Supermarket\Model\SupermarketGoods'])
            ->join('Core\Model\VersionControl','vc.table_id = cts.id and vc.table_name="cms_home_top_slide"','vc','LEFT')
            ->where('is_selling = :selling: and status = :valid: and title like :goodsName:',['store'=>$store])
            ->andWhere('b.store = :store: AND b.active=1 and platforms like :systemType:',['store'=>$store,'systemType' => '%'.$systemType.'%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();*/
        //标题、图片、初始价格、单独购买价格、描述、位置、推荐、排序、点赞、销量
        $goodsName = trim($goodsName);
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            // ->columns('id,stock,title,img_url,original_price,self_price,description,location,is_recommend,sort,base_fav_count,base_order_count')
            ->from(['sg' => 'Ticket\Model\Ticket'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->andWhere('sg.title like :goodsName: ', ['goodsName' => '%' . $goodsName . '%'])
            ->orderBy('sort')
            ->getQuery()
            ->execute();
        return $goods;
    }

    public function getList($page = 1, $pageSize = 10)
    {
        //标题、图片、初始价格、单独购买价格、描述、位置、推荐、排序、点赞、销量
        //分页
        $start = ($page - 1) * $pageSize;
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            // ->columns('id,stock,title,img_url,original_price,self_price,description,location,is_recommend,sort,base_fav_count,base_order_count')
            ->from(['sg' => 'Ticket\Model\Ticket'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->orderBy('sort')
            ->limit($start, $pageSize)
            ->getQuery()
            ->execute();
        return $goods;

    }

    public function getFirst()
    {
        $condition = " is_selling = " . $this->_config['selling_status']['selling'];
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $condition .= " order by sort";
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }


    public function judgeUser($id, $userId)
    {
        $condition = " id = " . $id;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        if (!empty($goods)) {
            if (isset($goods->user_id) && $goods->user_id == $userId) {
                return true;
            } else {
                //查询是否同一个商户不同用户
                $user = $this->app->tencent->api->UserApi()->detail($userId);
                if (!empty($user)) {
                    if (isset($user->merchant_id) && isset($goods->merchant_id) && $user->merchant_id == $goods->merchant_id) {
                        return true;
                    } else {
                        return false;

                    }
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }

    }


}