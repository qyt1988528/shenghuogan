<?php

namespace User\Api;

use MDK\Api;

class Helper extends Api
{
    private $_config;
    private $_model;
    private $_certStatus;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = $this->app->parttimejob->api->Certification();
        $this->_certStatus = $this->app->core->config->certification->toArray();
    }


    public function createCert($postData)
    {
        try {
            //校验必填字段
            $requiredFields = $this->_model->getInsertFields();
            foreach ($requiredFields as $v) {
                if(empty($postData[$v])){
                    return 0;
                }
            }
            //创建之前,通过用户ID 校验是否存在 待审核 或 已通过 存在则不创建
            $certData = $this->_model->detailByUserId($postData['upload_user_id']);
            if(empty($certData)){
                //不存在 创建
                return $this->_model->createRecord($postData);
            }else{
                if($certData['certification_status'] == $this->_certStatus['certification_status']['refused']['code']){
                    //未通过的 先置原数据status为-1 再新增
                    $deleteRet = $this->_model->deleteRecord($certData['id']);
                    if($deleteRet){
                        return $this->_model->createRecord($postData);
                    }
                }
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    public function updateCert($postData)
    {
        try {
            $requiredFields = $this->_model->getInsertFields();
            foreach ($requiredFields as $v) {
                if(empty($postData[$v])){
                    return false;
                }
            }
            //更新的操作实际上就是将上次的未通过的记录置status为-1，新增一条记录
            $updateModel = $this->_model->detail($postData['id'])->toArray();
            if (empty($updateModel)) {
                return false;
            }
            $oldUploadUserId = $updateModel['upload_user_id'] ?? 0;
            $newUploadUserId = $postData['upload_user_id'] ?? 0;
            //判断修改人是否存在权限
            if($oldUploadUserId != $newUploadUserId){
                return false;
            }
            if($updateModel['certification_status'] == $this->_certStatus['certification_status']['refused']['code']){
                //未通过的 先置原数据status为-1 再新增
                $deleteRet = $this->_model->deleteRecord($updateModel['id']);
                if($deleteRet){
                    return $this->_model->createRecord($postData);
                }
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }


    public function detail($userId=0)
    {
        //获取最近一条审核记录
        return $this->_model->detailByUserId($userId);
    }


}