<?php
namespace Supermarket\Api;

use MDK\Api;
use Supermarket\Model\SupermarketGoods;

class Helper extends Api
{
    private $_config;
    private $_model;
    public function __construct() {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new SupermarketGoods();
    }

    public function getInsertFields(){
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
            'merchant_id',
        ];
    }
    public function getDefaultInsertFields($postData){
        $defaultInsertFields = [
            'is_selling' => $this->_config['selling_status']['selling'],
            'base_fav_count' => mt_rand(20,50),
            'base_order_count' => mt_rand(20,50),
            'create_time' => date('Y-m-d H:i:s'),
        ];
        if(!isset($postData['together_price']) || empty($postData['together_price'])){
            $defaultInsertFields['together_price'] = $postData['self_price'];
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
        $condition .= " and is_selling = ".$this->_config['selling_status']['selling'];
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }
    public function search($goodsName){
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Supermarket\Model\SupermarketGoods'])
            ->where('sg.is_selling = :selling: ',['selling'=>$this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->andWhere('sg.title like :goodsName: ',['goodsName' => '%'.$goodsName.'%'])
            ->orderBy('sort')
            ->getQuery()
            ->execute();
        return $goods;
    }
    public function getList(){
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Supermarket\Model\SupermarketGoods'])
            ->where('sg.is_selling = :selling: ',['selling'=>$this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->orderBy('sort')
            ->getQuery()
            ->execute();
        return $goods;
    }

}