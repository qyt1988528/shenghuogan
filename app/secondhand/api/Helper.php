<?php
namespace Secondhand\Api;

use MDK\Api;
use Secondhand\Model\Second;

class Helper extends Api
{
    private $_config;
    private $_model;
    public function __construct() {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new Second();
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
            // 'merchant_id',
        ];
    }
    public function getDefaultInsertFields($postData){
        $defaultInsertFields = [
            'is_selling' => $this->_config['selling_status']['selling'],
            'create_time' => date('Y-m-d H:i:s'),
            'publish_time' => date('Y-m-d H:i:s'),
            'user_id' => $postData['user_id'] ?? 0,
            'merchant_id' => $postData['merchant_id'] ?? 0,
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
            $judgeResult = $this->judgeUser($postData['id'],$postData['user_id']);
            if($judgeResult == false){
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
    public function withdrawSecond($goodsId,$userId){
        try{
            $updateModel = $this->_model->findFirstById($goodsId);
            if(empty($updateModel)){
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
        }catch (\Exception $e){
            return false;
        }
    }
    public function deleteSecond($goodsId,$userId){
        try{
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($goodsId);
            if(empty($updateModel)){
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
        }catch (\Exception $e){
            return false;
        }
    }
    public function detail($goodsId,$userId=0){
        $condition = "id = ".$goodsId;
        $judgeResult = $this->judgeUser($goodsId,$userId);
        if($judgeResult == false){
            $condition .= " and is_selling = ".$this->_config['selling_status']['selling'];
        }
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
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

    public function getList($page=1,$pageSize=10){
        //标题、图片、初始价格、单独购买价格、描述、位置、推荐、排序、点赞、销量
        //分页
        $start = ($page-1)*$pageSize;
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Secondhand\Model\Second'])
            ->where('sg.is_selling = :selling: ',['selling'=>$this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->orderBy('publish_time desc')
            ->limit($pageSize,$start)
            ->getQuery()
            ->execute();
        return $goods;

    }
    public function getListByMerchantId($merchantId){
        $houses = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Secondhand\Model\Second'])
            // ->where('sg.is_hiring = :hiring: ', ['hiring' => $this->_config['hiring_status']['hiring']])
            ->andWhere('sg.merchant_id = :merchant_id: ', ['merchant_id' => $merchantId])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->orderBy('publish_time desc')
            ->getQuery()
            ->execute();
        return $houses;

    }
    public function getFirst(){
        /*
        $condition = " is_selling = ".$this->_config['selling_status']['selling'];
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $condition .= " order by sort";
        $goods = $this->_model->findFirst($condition);
        */

        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Secondhand\Model\Second'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->orderBy('sort')
            ->getQuery()
            ->getSingleResult();
        return $goods;
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