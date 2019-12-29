<?php
namespace Express\Api;

use Express\Model\ExpressSend;
use MDK\Api;

class Send extends Api
{
    private $_config;
    private $_model;
    public function __construct() {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new ExpressSend();
    }

    public function getInsertFields(){
        return $insertFields = [
            'express_company_id',
            'address_id',
            'user_address_id',
            'gratuity',
            'publish_user_id',
        ];
    }
    public function getDefaultInsertFields($postData){
        $defaultInsertFields = [
            'is_hiring' => $this->_config['hiring_status']['hiring'],
            'create_time' => date('Y-m-d H:i:s'),
            'publish_time' => date('Y-m-d H:i:s'),
            'remarks' => $postData['remarks'] ?? '',
            // 'publish_user_id' => $postData['publish_user_id'] ?? 0
        ];
        if(!isset($postData['together_price']) || empty($postData['together_price'])){
            $defaultInsertFields['together_price'] = $postData['self_price'];
        }
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }
    public function createSend($postData){
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
    public function updateSend($postData){
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
    public function withdrawSend($goodsId){
        try{
            $updateModel = $this->_model->findFirstById($goodsId);
            if(empty($updateModel)){
                return false;
            }
            $updateData = [
                'id' => $goodsId,
                'is_hiring' => $this->_config['hiring_status']['unhiring'],
            ];
            $updateModel->update($updateData);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    public function deleteSend($goodsId){
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
        $condition .= " and is_hiring = ".$this->_config['hiring_status']['hiring'];
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }
    /*
    public function search($goodsName){
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Secondhand\Model\Second'])
            ->where('sg.is_hiring = :hiring: ',['hiring'=>$this->_config['hiring_status']['hiring']])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->andWhere('sg.title like :goodsName: ',['goodsName' => '%'.$goodsName.'%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();
        return $goods;
    }
    */

    public function getList($page=1,$pageSize=10){
        //标题、图片、初始价格、单独购买价格、描述、位置、推荐、排序、点赞、销量
        //分页
        $start = ($page-1)*$pageSize;
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Express\Model\ExpressSend'])
            ->where('sg.is_hiring = :hiring: ',['hiring'=>$this->_config['hiring_status']['hiring']])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->orderBy('publish_time desc')
            ->limit($start,$pageSize)
            ->getQuery()
            ->execute();
        return $goods;

    }

}