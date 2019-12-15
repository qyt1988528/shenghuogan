<?php
namespace Rent\Api;

use MDK\Api;
use Rent\Model\RentCar;

class House extends Api
{
    private $_config;
    private $_model;
    public function __construct() {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new RentCar();
    }

    public function getInsertFields(){
        return $insertFields = [
            'img_url',
            'title',
            'location',
            'rental',
            'square',
            'orientations',
            'room',
            'parlour',
            'toilet',
            'cellphone',
            'qq',
            'wechat',
            'description',
        ];
    }
    public function getDefaultInsertFields($postData){
        $defaultInsertFields = [
            'create_time' => date('Y-m-d H:i:s'),
            'publish_time' => date('Y-m-d H:i:s'),
        ];
        if (!empty($postData['title'])) {
            // $defaultInsertFields['title_pinyin'] = $this->app->core->api->Pinyin()->getpy($postData['title']);
        }
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }
    public function createGoods($postData){
        try{
            $insertData = $this->getDefaultInsertFields($postData);
            foreach ($this->getInsertFields() as $v){
                $insertData[$v] = $postData[$v];
            }
            $model = $this->_model;
            $model->create($insertData);
            return !empty($model->id) ? $model->id : 0;
        }catch (\Exception $e){
            return 0;
        }
    }
    public function updateGoods($postData){
        try{
            $updateData = ['id' => $postData['id']];
            $updateModel = $this->_model->findFirstById($postData['id']);
            if(empty($updateModel)){
                return false;
            }
            foreach ($this->getInsertFields() as $v){
                $updateData[$v] = $postData[$v];
            }
            $updateModel->update($updateData);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    //下架
    public function withdrawGoods($goodsId){
        try{
            $updateModel = $this->_model->findFirstById($goodsId);
            if(empty($updateModel)){
                return false;
            }
            $updateData = [
                'id' => $goodsId,
                'is_selling' => $this->_config['selling_status']['unselling'],
            ];
            $updateModel->update($updateData);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    public function deleteGoods($goodsId){
        try{
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($goodsId);
            if(empty($updateModel)){
                return false;
            }
            $updateData = [
                'id' => $goodsId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    public function detail($goodsId){
        $condition = "id = ".$goodsId;
        // $condition .= " and is_selling = ".$this->_config['selling_status']['selling'];
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }
    public function search($condition,$pageSize=10){
        //标题、图片、初始价格、单独购买价格、描述、位置、推荐、排序、点赞、销量
        $sql = ' 1=1 ';
        if(!empty($condition['title'])){
            $sql .= ' and sg.title like "%'.$condition['title'].'%" ';
        }
        if(!empty($condition['room'])){
            $sql .= ' and sg.room = '.$condition['room'].' ';
        }
        if(!empty($condition['price_min'])){
            $sql .= ' and sg.rental > '.$condition['price_min'].' ';
        }
        if(!empty($condition['price_max'])){
            $sql .= ' and sg.rental < '.$condition['price_max'].' ';
        }
        if(!empty($condition['page'])){
            $start = ($condition['page']-1)*$pageSize;
        }else{
            $start = 0;
        }

        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Rent\Model\RentHouse'])
            // ->where('sg.is_selling = :selling: ',['selling'=>$this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->andWhere($sql)
            ->orderBy('publish_time desc')
            ->limit($start,$pageSize)
            ->getQuery()
            ->execute();
        return $goods;
    }

    public function getList($page=1,$pageSize=10){
        //标题、图片、初始价格、单独购买价格、描述、位置、推荐、排序、点赞、销量
        //分页
        $start = ($page-1)*$pageSize;
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Ticket\Model\Ticket'])
            // ->where('sg.is_selling = :selling: ',['selling'=>$this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->orderBy('publish_time desc')
            ->limit($start,$pageSize)
            ->getQuery()
            ->execute();
        return $goods;

    }

}