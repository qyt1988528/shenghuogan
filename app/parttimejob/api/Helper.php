<?php

namespace Parttimejob\Api;

use MDK\Api;
use Parttimejob\Model\Parttimejob;

class Helper extends Api
{
    private $_config;
    private $_model;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new Parttimejob();
    }

    public function getInsertFields()
    {
        return $insertFields = [
            'title',
            'description',
            'commission',
            'location',
            'cellphone',
            'qq',
            'wechat',
            'user_id',
        ];
    }

    public function getDefaultInsertFields($postData)
    {
        $defaultInsertFields = [
            'is_hiring' => $this->_config['hiring_status']['hiring'],
            'create_time' => date('Y-m-d H:i:s'),
            'publish_time' => date('Y-m-d H:i:s'),
            'base_views' => mt_rand(3,10),
            'user_id' => $postData['user_id'] ?? 0,
            'merchant_id' => $postData['merchant_id'] ?? 0,
        ];
        if (!empty($postData['title'])) {
            $defaultInsertFields['title_pinyin'] = $this->app->core->api->Pinyin()->getpy($postData['title']);
        }
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }

    public function createParttimejob($postData)
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

    public function updateParttimejob($postData)
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
    public function withdrawParttimejob($parttimejobId,$userId)
    {
        try {
            $updateModel = $this->_model->findFirstById($parttimejobId);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($parttimejobId,$userId);
            if($judgeResult == false){
                return false;
            }
            $updateData = [
                'id' => $parttimejobId,
                'is_hiring' => $this->_config['hiring_status']['unhiring'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteParttimejob($parttimejobId,$userId)
    {
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($parttimejobId);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($parttimejobId,$userId);
            if($judgeResult == false){
                return false;
            }
            $updateData = [
                'id' => $parttimejobId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($parttimejobId,$userId=0)
    {
        $condition = "id = " . $parttimejobId;
        $judgeResult = $this->judgeUser($parttimejobId,$userId);
        if($judgeResult == false){
            $condition .= " and is_hiring = " . $this->_config['hiring_status']['hiring'];
        }
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        if(!empty($goods)){
            //点击量+1
            $updateData = [
                'id' => $goods->id ?? 0,
                'views' => ($goods->views ?? 0) +1,
            ];
            $updateModel = $this->_model->findFirstById($goods->id);
            $updateModel->update($updateData);
            $goods = $this->addTotalField($goods);
        }
        return $goods;
    }
    public function addTotalField($obj){
        $arr = [];
        if(!empty($obj)){
            foreach ($obj as $k=>$v){
                $arr[$k] = $v;
            }
            $arr['total_views'] = ($arr['views'] ?? 0) + ($arr['base_views'] ?? 0);
        }
        return (object)$arr;
    }

    public function search($goodsName)
    {
        /*
        $slideAds = $this->modelsManager->createBuilder()
            ->columns('cts.image_ratio,cts.image,cts.jump_url,cts.title,cts.ga_name,vc.condition')
            ->from(['cts'=>'Supermarket\Model\SupermarketGoods'])
            ->join('Core\Model\VersionControl','vc.table_id = cts.id and vc.table_name="cms_home_top_slide"','vc','LEFT')
            ->where('is_selling = :selling: and status = :valid: and title like :goodsName:',['store'=>$store])
            ->andWhere('b.store = :store: AND b.active=1 and platforms like :systemType:',['store'=>$store,'systemType' => '%'.$systemType.'%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();*/
        $goods = $this->modelsManager->createBuilder()
            ->columns('id,user_id,title,description,location,commission,cellphone,qq,wechat,is_hiring,publish_time,end_time,views,base_views,sort,goods_type,status,(views+base_views) as total_views')
            ->from(['sg' => 'Parttimejob\Model\Parttimejob'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->andWhere('sg.title like :goodsName: ', ['goodsName' => '%' . $goodsName . '%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();
        return $goods;
    }

    public function getList($page = 1, $pageSize = 10){
        $start = ($page - 1) * $pageSize;
        $goods = $this->modelsManager->createBuilder()
            ->columns('id,user_id,title,description,location,commission,cellphone,qq,wechat,is_hiring,publish_time,end_time,views,base_views,sort,goods_type,status,(views+base_views) as total_views')
            ->from(['sg' => 'Parttimejob\Model\Parttimejob'])
            ->where('sg.is_hiring = :hiring: ', ['hiring' => $this->_config['hiring_status']['hiring']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->orderBy('sort')
            ->limit($start, $pageSize)
            ->getQuery()
            ->execute();
        // var_dump($goods->toArray());exit;
        return $goods;
    }
    public function getListByUserId($userId){
         $jobs = $this->modelsManager->createBuilder()
             ->columns('id,user_id,title,description,location,commission,cellphone,qq,wechat,is_hiring,publish_time,end_time,views,base_views,sort,goods_type,status,(views+base_views) as total_views')
            ->from(['sg' => 'Parttimejob\Model\Parttimejob'])
            ->where('sg.is_hiring = :hiring: ', ['hiring' => $this->_config['hiring_status']['hiring']])
             ->andWhere('sg.user_id = :user_id: ', ['user_id' => $userId])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->getQuery()
            ->execute();
        return $jobs;

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