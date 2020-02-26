<?php

namespace Supermarket\Api;

use MDK\Api;
use Supermarket\Model\SupermarketGoods;

class Helper extends Api
{
    private $_config;
    private $_model;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new SupermarketGoods();
    }

    public function getInsertFields()
    {
        return $insertFields = [
            'title',
            'img_url',
            'type_id',
            'cost_price',
            'original_price',
            'self_price',
            'description',
            'specs',
            'specs_unit_id',
            'stock',
            // 'merchant_id',
        ];
    }

    public function getDefaultInsertFields($postData)
    {
        $defaultInsertFields = [
            'is_selling' => $this->_config['selling_status']['selling'],
            'base_fav_count' => mt_rand(20, 50),
            'base_order_count' => mt_rand(20, 50),
            'create_time' => date('Y-m-d H:i:s'),
            'is_recommend' => $postData['is_recommend'] ?? -1,
            'user_id' => $postData['user_id'] ?? 0,
            'merchant_id' => $postData['merchant_id'] ?? 0,
        ];
        if (!isset($postData['together_price']) || empty($postData['together_price'])) {
            $defaultInsertFields['together_price'] = $postData['self_price'];
        }
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }

    public function createGoods($postData)
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

    public function updateGoods($postData)
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
    public function withdrawGoods($goodsId,$userId)
    {
        try {
            $updateModel = $this->_model->findFirstById($goodsId);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($goodsId,$userId);
            if($judgeResult == false){
                return false;
            }
            $updateData = [
                'id' => $goodsId,
                'is_selling' => $this->_config['selling_status']['unselling'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteGoods($goodsId,$userId)
    {
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($goodsId);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($goodsId,$userId);
            if($judgeResult == false){
                return false;
            }
            $updateData = [
                'id' => $goodsId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($goodsId)
    {
        $condition = "id = " . $goodsId;
        $condition .= " and is_selling = " . $this->_config['selling_status']['selling'];
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }

    public function search($goodsName = '', $typeId = 0)
    {
        $sqlName = $sqlType = ' 1=1 ';
        if ($typeId != 0) {
            $sqlType .= ' and sg.type_id = ' . $typeId . ' ';
        }
        if (!empty($goodsName)) {
            $sqlName .= ' and sg.title = "%' . $goodsName . '%" ';
        }
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => 'Supermarket\Model\SupermarketGoods'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->andWhere($sqlName)
            ->andWhere($sqlType)
            ->orderBy('sort')
            ->getQuery()
            ->execute();
        return $goods;
    }

    public function getList($page = 1, $pageSize = 10)
    {
        $start = ($page - 1) * $pageSize;
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => 'Supermarket\Model\SupermarketGoods'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->orderBy('sort')
            ->limit($start, $pageSize)
            ->getQuery()
            ->execute();
        return $goods;
    }

    public function getRecommendList($page = 1, $pageSize = 10)
    {
        $start = ($page - 1) * $pageSize;
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => 'Supermarket\Model\SupermarketGoods'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->orderBy('is_recommend desc')
            ->limit($start, $pageSize)
            ->getQuery()
            ->execute();
        return $goods;
    }

    public function getIndexData($typeId = 0,$page=1)
    {
        //type_list type_title,type_id,selected
        $typeList = [];
        $typeList[] = [
            'type_id' => 0,
            'type_title' => '全部',
            'selected' => 0 == $typeId ? true : false,
        ];

        $supermarketGoodsType = $this->_config['supermarket_goods_type'];
        foreach ($supermarketGoodsType as $type_id => $type_title) {
            $typeList[] = [
                'type_id' => $type_id,
                'type_title' => $type_title,
                'selected' => $type_id == $typeId ? true : false,
            ];
        }
        $data['type_list'] = $typeList;
        //recommend_list
        $data['recommend_list'] = $this->getRecommendList(1,3);
        //goods_list
        $data['goods_list'] = $this->getList($page);
        return (object)$data;

    }

    public function judgeUser($id,$userId){
        $condition = " id = ".$id;
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        if(!empty($goods)){
            if(isset($goods->user_id) && $goods->user_id==$userId){
                return true;
            }else{
                //查询是否同一个商户不同用户
                $user = $this->app->tencent->api->UserApi()->detail($userId);
                if(!empty($user)){
                    if(isset($user->merchant_id) && isset($goods->merchant_id) && $user->merchant_id == $goods->merchant_id){
                        return true;
                    }else{
                        return false;

                    }
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }

    }

}