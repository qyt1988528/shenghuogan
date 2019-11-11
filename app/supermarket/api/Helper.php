<?php
namespace Supermarket\Api;

use MDK\Api;
use Supermarket\Model\SupermarketGoods;

class Helper extends Api
{
    private $_config;
    public function __construct() {
        $this->_config = $this->app->core->config->config->toArray();
    }

    public function getInsertFields(){
        return $insertFields = [
            'title',
            'img_url',
            'type_id',
            'original_price',
            'self_price',
            'description',
            'specs',
            'specs_unit_id',
            'stock',
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
            $model = new SupermarketGoods();
            $model->create($insertData);
            return !empty($model->id) ? $model->id : 0;
        }catch (\Exception $e){
            return 0;
        }
    }
    public function updateGoods($postData){
        try{
            $updateData = ['id' => $postData['id']];
            $updateModel = SupermarketGoods::findFirstById($postData['id']);
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
            $updateModel = SupermarketGoods::findFirstById($goodsId);
            if(empty($updateModel)){
                return false;
            }
            $updateData = [
                'id' => $goodsId,
                'is_selling' => $this->_config['selling_status']['selling'],
            ];
            $updateModel->update($updateData);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    public function deleteGoods($goodsId){
        try{
            $invalid = $this->_config['status']['invalid'];
            $updateModel = SupermarketGoods::findFirstById($goodsId);
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

}