<?php

namespace Address\Api;

use Address\Model\Address;
use Address\Model\Region;
use MDK\Api;

class Helper extends Api
{
    private $_config;
    private $_model;
    private $_region_model;
    private $_china_id;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new Address();
        $this->_region_model = new Region();
        $this->_china_id = 100000;
    }

    public function getInsertFields()
    {
        return $insertFields = [
            'user_id',
            'name',
            'cellphone',
            'province_id',
            'city_id',
            'county_id',
            'detailed_address',
        ];
    }

    public function getDefaultInsertFields($postData)
    {
        $defaultInsertFields = [
            'is_default' => $this->_config['address_status']['undefault'],
            'create_time' => date('Y-m-d H:i:s'),
        ];
        return $defaultInsertFields;
    }

    public function createAddress($postData)
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

    public function updateAddress($postData)
    {
        try {
            $updateData = ['id' => $postData['id']];
            $updateModel = $this->_model->findFirstById($postData['id']);
            if (empty($updateModel)) {
                return false;
            }
            // $updateData = $this->getDefaultInsertFields($postData);
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
    public function withdrawAddress($goodsId)
    {
        try {
            $updateModel = $this->_model->findFirstById($goodsId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $goodsId,
                'is_selling' => $this->_config['selling_status']['unselling'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteAddress($postData)
    {
        if(empty($postData['id']) || empty($postData['user_id'])){
            return false;
        }
        //权限
        $addressData = $this->detail($postData['id']);
        if(empty($addressData) || (int)$addressData->user_id != (int)$postData['user_id']){
            return false;
        }
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($postData['id']);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $postData['id'],
                'status' => $invalid,
            ];
            return $updateModel->update($updateData);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($goodsId)
    {
        $condition = "id = " . $goodsId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }

    /*
    public function search($goodsName){
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg'=>'Address\Model\Address'])
            ->andWhere('sg.status = :valid: ',['valid'=>$this->_config['data_status']['valid']])
            ->andWhere('sg.title like :goodsName: ',['goodsName' => '%'.$goodsName.'%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();
        return $goods;
    }
    */

    public function getList($page = 1, $pageSize = 10)
    {
        //标题、图片、初始价格、单独购买价格、描述、位置、推荐、排序、点赞、销量
        //分页
        $start = ($page - 1) * $pageSize;
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => 'Address\Model\Address'])
            ->where('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->orderBy('create_time desc')
            ->limit($start, $pageSize)
            ->getQuery()
            ->execute();
        return $goods;

    }

    public function getListByPid($pid=0,$level=0){
        $colums = 'id,name,pid';
        switch ($level){
            case $this->_config['region_level']['province'] - 1:
                $colums .=',id as province_id';
                break;
            case $this->_config['region_level']['city'] - 1:
                $colums .=',id as city_id';
                break;
            case $this->_config['region_level']['county'] - 1:
                $colums .=',id as county_id';
                break;
        }
        $areaList = $this->modelsManager->createBuilder()
            ->columns($colums)
            ->from(['sg' => 'Address\Model\Region'])
            ->where('sg.pid = :pid: ', ['pid' => $pid==0 ?  $this->_china_id : $pid])
            ->orderBy('id')
            ->getQuery()
            ->execute();
        return $areaList;
    }
    public function getRegionById($regionId){
        $regionModel = new Region();
        $condition = "id = " . $regionId;
        $regionData = $regionModel->findFirst($condition);
        return $regionData;
    }

    public function getAddressListByUserId($userId){
        $condition = "user_id = " . $userId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $addressList = $this->_model->find($condition)->toArray();
        foreach ($addressList as &$al){
            $al['address_name'] = $this->getTitleByCountyId($al['county_id']);
        }
        return $addressList;
    }

    //通过区ID获取省市区名称
    public function getTitleByCountyId($countyId){
        if(empty($countyId)){
            return '';
        }
        $countyId = (int)$countyId;
        $county = $this->_region_model->findFirstById($countyId);
        // var_dump($county->name);exit;
        if(empty($county)){
            return '';
        }
        $city = $this->_region_model->findFirstById($county->pid);
        if(empty($city)){
            return '';
        }
        $province = $this->_region_model->findFirstById($city->pid);
        if(empty($province)){
            return '';
        }
        $title = $province->name . $city->name . $county->name;
        return $title;
    }

}