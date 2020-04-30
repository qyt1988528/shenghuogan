<?php

namespace Platform\Controller;

use MDK\Controller;


/**
 * Index controller.
 * @RoutePrefix("/platform", name="platform")
 */
class IndexController extends Controller
{
    private $_error;
    private $_userId;
    private $_platformId;
    private $_config;

    public function initialize()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_error = $this->_config['error_message'];
        //验证用户是否登录
        $this->_userId = $this->app->tencent->api->UserApi()->getUserId();
        if (empty($this->_userId)) {
            $this->resultSet->error(1010, $this->_error['unlogin']);
            exit;
        }
        //验证是否为平台用户

        $this->_platformId = $this->app->tencent->api->UserApi()->getPlatformIdByUserId($this->_userId);
        if (empty($this->_platformId)) {
            $this->resultSet->error(1011, $this->_error['unplatform']);
            exit;
        }
    }


    /**
     * 创建快递的 规格 和 可选服务
     * Create action.
     * @return void
     * @Route("/createExpress", methods="POST", name="platform")
     */
    public function createExpressAction() {
        //权限验证
        $postData = $this->request->getPost();
        $postData['publish_user_id'] = $this->_userId;
        // $postData['merchant_id'] = $this->_merchantId;
        $insertFields = $this->app->express->api->ExpressAdmin()->getInsertFields();
        foreach ($insertFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $insert = $this->app->express->api->ExpressAdmin()->create($postData);
            if(empty($insert)){
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
            $data['data'] =[
                'id' => $insert
            ];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 删除快递的 规格 和 可选服务
     * Delete action.
     * @return void
     * @Route("/deleteExpress", methods="POST", name="platform")
     */
    public function deleteExpressAction() {
        //权限验证
        $secondId = $this->request->getPost('id');
        if(empty($secondId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->express->api->ExpressAdmin()->delete($secondId,$this->_userId);
           if($result){
               $data['data'] = [
                   'del_success' => $result
               ];
           }else{
               $this->resultSet->error(1002,$this->_error['try_later']);
           }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }
    /**
     * 创建
     * Create action.
     * @return void
     * @Route("/createMerchant", methods="POST", name="platform")
     */
    public function createMerchantAction() {
        //权限验证
        $postData = $this->request->getPost();
        $postData['user_id'] = $this->_userId;
        $insertFields = $this->app->merchant->api->MerchantManage()->getInsertFields();
        foreach ($insertFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input'].' error field:  '.$v);
            }
        }
        try{
            $verifyPhone = $this->app->core->api->Phone()->checkPhone($postData['cellphone']);
            if(!$verifyPhone){
                $this->resultSet->error(1002,$this->_error['cellphone']);
            }
            $insert = $this->app->merchant->api->MerchantManage()->createMerchant($postData);
            if(empty($insert)){
                $this->resultSet->error(1003,$this->_error['try_later']);
            }
            $data['data'] =[
                'id' => $insert
            ];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 删除
     * Create action.
     * @return void
     * @Route("/deleteMerchant", methods="POST", name="platform")
     */
    public function deleteMerchantAction() {
        //权限验证
        $cateringId = $this->request->getPost('id');
        if(empty($cateringId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->merchant->api->MerchantManage()->deleteMerchant($cateringId,$this->_userId);
           if($result){
               $data['data'] = [
                   'del_success' => $result
               ];
           }else{
               $this->resultSet->error(1002,$this->_error['try_later']);
           }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }
    /**
     * 下架
     * Create action.
     * @return void
     * @Route("/closingMerchant", methods="POST", name="platform")
     */
    public function closingMerchantAction() {
        //权限验证
        $cateringId = $this->request->getPost('id');
        if(empty($cateringId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->merchant->api->MerchantManage()->withdrawMerchant($cateringId,$this->_userId);
           if($result){
               $data['data'] = [
                   'closing_success' => $result
               ];
           }else{
               $this->resultSet->error(1002,$this->_error['try_later']);
           }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 上架
     * Create action.
     * @return void
     * @Route("/openingMerchant", methods="POST", name="platform")
     */
    public function openingMerchantAction() {
        //权限验证
        $cateringId = $this->request->getPost('id');
        if(empty($cateringId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->merchant->api->MerchantManage()->unwithdrawMerchant($cateringId,$this->_userId);
           if($result){
               $data['data'] = [
                   'opening_success' => $result
               ];
           }else{
               $this->resultSet->error(1002,$this->_error['try_later']);
           }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 修改商户信息
     * Create action.
     * @return void
     * @Route("/updateMerchant", methods="POST", name="platform")
     */
    public function updateMerchantAction() {
        //权限验证
        $postData = $this->request->getPost();
        if(empty($postData['id'])){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        $postData['user_id'] = $this->_userId;
        $updateFields = $this->app->merchant->api->MerchantManage()->getInsertFields();
        foreach ($updateFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $verifyPhone = $this->app->core->api->Phone()->checkPhone($postData['cellphone']);
            if(!$verifyPhone){
                $this->resultSet->error(1002,$this->_error['cellphone']);
            }
            $result = $this->app->merchant->api->MerchantManage()->updateMerchant($postData);
            if($result){
                $data['data'] = [
                    'update_success' => $result
                ];
            }else{
                $this->resultSet->error(1003,$this->_error['try_later']);
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 商户列表(名称和手机号查询)
     * @return void
     * @Route("/listMerchant", methods="GET", name="platform")
     */
    public function listMerchantAction() {
        $keywords = $this->request->getParam('keywords',null,'');
        $page = $this->request->getParam('page',null,1);
        //分页
        try{
            $data['data'] = [];
            $merchants = $this->app->merchant->api->MerchantManage()->getList($keywords,$page);
            if(!empty($merchants)){
                $data['data'] = $merchants;
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }

    /**
     * 商户详情
     * @return void
     * @Route("/detailMerchant", methods="GET", name="platform")
     */
    public function detailMerchantAction(){
        $goodsId = $this->request->getParam('id',null,'');
        if(empty($goodsId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
            $result = $this->app->merchant->api->MerchantManage()->detail($goodsId,$this->_userId);
            if(empty($result)){
                $this->resultSet->error(1002,$this->_error['not_exist']);
            }
            $data['data'] = $result;
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

}
