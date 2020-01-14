<?php

namespace Hotel\Api;

use Hotel\Model\Hotel;
use MDK\Api;

class Helper extends Api
{
    private $_config;
    private $_model;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new Hotel();
    }

    public function getInsertFields()
    {
        return $insertFields = [
            'img_url',
            'title',
            'stock',//房间数
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
        ];
        if (!isset($postData['together_price']) || empty($postData['together_price'])) {
            $defaultInsertFields['together_price'] = $postData['self_price'];
        }
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }

    public function createHotel($postData)
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

    public function updateHotel($postData)
    {
        try {
            $updateData = ['id' => $postData['id']];
            $updateModel = $this->_model->findFirstById($postData['id']);
            if (empty($updateModel)) {
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
    public function withdrawHotel($hotelId)
    {
        try {
            $updateModel = $this->_model->findFirstById($hotelId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $hotelId,
                'is_selling' => $this->_config['selling_status']['unselling'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteHotel($hotelId)
    {
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($hotelId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $hotelId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($hotelId)
    {
        $condition = "id = " . $hotelId;
        $condition .= " and is_selling = " . $this->_config['selling_status']['selling'];
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }

    public function search($goodsName)
    {
        //暂时不支持房间搜索
        $goodsName = trim($goodsName);
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            // ->columns('id,stock,title,img_url,original_price,self_price,description,location,is_recommend,sort,base_fav_count,base_order_count')
            ->from(['sg' => 'Hotel\Model\Hotel'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->andWhere('sg.title like :goodsName: ', ['goodsName' => '%' . $goodsName . '%'])
            ->orderBy('sort desc')
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
            ->from(['sg' => 'Hotel\Model\Hotel'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->orderBy('sort')
            ->limit($start, $pageSize)
            ->getQuery()
            ->execute();
        return $goods;

    }

    public function getFirst(){
        $condition = " is_selling = ".$this->_config['selling_status']['selling'];
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $condition .= " order by sort";
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }


}