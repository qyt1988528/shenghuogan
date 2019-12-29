<?php
namespace Express\Api;

use Express\Model\Express;
use MDK\Api;

class Helper extends Api
{
    private $_config;
    private $_model;
    public function __construct() {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new Express();
    }

    public function getInsertFields(){
        return $insertFields = [
            'img_url',
            'title',
            'location',
            'cost_price',
            'original_price',
            'self_price',
            'stock',
            'cellphone',
            'qq',
            'wechat',
            'description',
        ];
    }
    public function getDefaultInsertFields($postData){
        $defaultInsertFields = [
            'is_selling' => $this->_config['selling_status']['selling'],
            'create_time' => date('Y-m-d H:i:s'),
            'publish_time' => date('Y-m-d H:i:s'),
        ];
        if(!isset($postData['together_price']) || empty($postData['together_price'])){
            $defaultInsertFields['together_price'] = $postData['self_price'];
        }
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }
    public function createSecond($postData){
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
    public function updateSecond($postData){
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
    public function withdrawSecond($goodsId){
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
    public function deleteSecond($goodsId){
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
    public function detail($id){
        $condition = "id = ".$id;
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $expressConfig = $this->_model->findFirst($condition);
        return $expressConfig;
    }
    public function search($goodsName){
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Secondhand\Model\Second'])
            ->where('sg.is_selling = :selling: ',['selling'=>$this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->andWhere('sg.title like :goodsName: ',['goodsName' => '%'.$goodsName.'%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();
        return $goods;
    }

    /**
     * 获取快递配置列表
     * 1-寄快递，2-取件规格，3-可选服务，4快递列表
     * @param int $typeId
     * @return mixed
     */
    public function getTypeList($typeId=1){
        //分页
        $goods = $this->modelsManager->createBuilder()
            ->columns('id,type_id,description,gratuity')
            ->from(['sg'=>'Express\Model\Express'])
            ->Where('sg.type_id = :type_id: ',['type_id'=>$typeId])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->getQuery()
            ->execute();
        return $goods;

    }

}