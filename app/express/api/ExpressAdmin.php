<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/4/30
 * Time: 下午12:33
 */
namespace Express\Api;

use Express\Model\Express as ExpressModel;
use MDK\Api;

class ExpressAdmin extends Api
{
    private $_config;
    private $_model;
    public function __construct() {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new ExpressModel();
    }

    public function getInsertFields(){
        return $insertFields = [
            'type_id',
            'description',
            'gratuity',
            'publish_user_id',
        ];
    }
    public function getDefaultInsertFields($postData){
        $defaultInsertFields = [
            'create_time' => date('Y-m-d H:i:s'),
            // 'publish_user_id' => $postData['publish_user_id'] ?? 0
        ];
        /*
        if(!isset($postData['together_price']) || empty($postData['together_price'])){
            $defaultInsertFields['together_price'] = $postData['self_price'] ;
        }*/
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }
    public function create($postData){
        try{
            $insertData = $this->getDefaultInsertFields($postData);
            foreach ($this->getInsertFields() as $v){
                $insertData[$v] = $postData[$v];
                // $insertData[$v] = htmlspecialchars($postData[$v]);
            }
            $model = $this->_model;
            // var_dump($insertData);exit;
            $ret = $model->create($insertData);
            /*
            if(empty($ret)){
                foreach ($model->getMessages() as $message) {
                    echo $message->getMessage(), "<br/>";
                }
            }exit;
            */
            return !empty($model->id) ? $model->id : 0;
        }catch (\Exception $e){
            // var_dump($e->getMessage());exit;
            return 0;
        }
    }
    public function update($postData){
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
    public function withdraw($goodsId,$userId){
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
                'is_hiring' => $this->_config['hiring_status']['unhiring'],
            ];
            $updateModel->update($updateData);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    public function delete($goodsId,$userId){
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
            ->execute()
            ->toArray();
        return $goods;

    }


    public function judgeUser($id,$userId){
        $condition = " id = ".$id;
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        if(!empty($goods)){
            //如果是默认值
            if(isset($goods->publish_user_id) && $goods->publish_user_id == 0){
                return true;
            }

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