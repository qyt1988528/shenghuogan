<?php
namespace Lostfound\Api;

use Lostfound\Model\Lostfound;
use MDK\Api;

class Helper extends Api
{
    private $_config;
    private $_model;
    public function __construct() {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new Lostfound();
    }

    public function getInsertFields(){
        return $insertFields = [
            'img_url',
            'title',
            'location',
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
            ->from(['sg'=>'Lostfound\Model\Lostfound'])
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
            ->from(['sg'=>'Lostfound\Model\Lostfound'])
            ->where('sg.is_selling = :selling: ',['selling'=>$this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->orderBy('publish_time desc')
            ->limit($start,$pageSize)
            ->getQuery()
            ->execute();
        return $goods;

    }

}