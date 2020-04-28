<?php
namespace Express\Api;

use Express\Model\ExpressTake;
use MDK\Api;

class Take extends Api
{
    private $_config;
    private $_model;
    public function __construct() {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new ExpressTake();
    }

    public function getInsertFields(){
        return $insertFields = [
            'address_id',
            'specs_id',
            'optional_service_id',
            'description',
            'num',
            'publish_user_id'
        ];
    }
    public function getDefaultInsertFields($postData){
        $defaultInsertFields = [
            'remarks' => $postData['remarks'] ?? '',
            'gratuity' => $postData['gratuity'] ?? 0,
            'is_hiring' => $this->_config['hiring_status']['hiring'],
            'create_time' => date('Y-m-d H:i:s'),
            'publish_time' => date('Y-m-d H:i:s'),
            'merchant_id' => $postData['merchant_id'] ?? 0,
            // 'publish_user_id' => $postData['publish_user_id'] ?? 0
        ];
        $defaultInsertFields['total_price'] = $this->calcTotalPrice($postData);
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }
    public function createTake($postData){
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
    public function updateTake($postData){
        try{
            $updateData = ['id' => $postData['id']];
            $updateModel = $this->_model->findFirstById($postData['id']);
            if(empty($updateModel)){
                return false;
            }
            $judgeResult = $this->judgeUser($postData['id'],$postData['publish_user_id']);
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
    public function withdrawTake($goodsId,$userId){
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
                'is_selling' => $this->_config['hiring_status']['unselling'],
            ];
            $updateModel->update($updateData);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    public function deleteTake($goodsId,$userId){
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
            $condition .= " and is_hiring = ".$this->_config['hiring_status']['hiring'];
        }
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }
    /*
    public function search($goodsName){
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Express\Model\ExpressTake'])
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
            ->from(['sg'=>'Express\Model\ExpressTake'])
            ->where('sg.is_hiring = :hiring: ',['hiring'=>$this->_config['hiring_status']['hiring']])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->orderBy('publish_time desc')
            ->limit($start,$pageSize)
            ->getQuery()
            ->execute()
            ->toArray();
        //取件规格 和 可选服务 的文字描述
        foreach ($goods as &$v){
            if(isset($v['specs_id']) && empty($v['specs_id'])){
                $desSpecs = $this->app->express->api->Helper()->getTypeList(2,$v['specs_id']);
                if(empty($desSpecs)){
                    $v['specs_description'] = '';
                }else{
                    $v['specs_description'] = $desSpecs['description'];
                }

            }
            if(isset($v['optional_service_id']) && empty($v['optional_service_id'])){
                $desOptional = $this->app->express->api->Helper()->getTypeList(3,$v['optional_service_id']);
                if(empty($desOptional)){
                    $v['optional_service_description'] = '';
                }else{
                    $v['optional_service_description'] = $desOptional['description'];
                }

            }
        }
        return $goods;

    }

    public function calcTotalPrice($postData){
        $total = 0;
        $num = $postData['num'] ?? 0;
        $num = (int) $num;
        //规格
        $specsId = $postData['specs_id'] ?? 0;
        $specsData = $this->app->express->api->Helper()->detail($specsId);
        if(!empty($specsData) && isset($specsData->gratuity)){
            $total += (float)$specsData->gratuity;
        }
        //可选
        $optionalServiceId = $postData['optional_service_id'] ?? 0;
        $optionalServiceData = $this->app->express->api->Helper()->detail($optionalServiceId);
        if(!empty($optionalServiceData) && isset($optionalServiceData->gratuity)){
            $total += (float)$optionalServiceData->gratuity;
        }
        $total = $total * $num;
        //小费
        if(isset($postData['gratuity'])){
            $total += (float)$postData['gratuity'];
        }
        return $total;
    }


    public function judgeUser($id,$userId){
        $condition = " id = ".$id;
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        if(!empty($goods)){
            if(isset($goods->publish_user_id) && $goods->publish_user_id==$userId){
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