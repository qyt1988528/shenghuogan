<?php
namespace Secondhand\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/secondadmin", name="secondadmin")
 */
class AdminController extends Controller
{
    private $_error;
    private $_userId;
    private $_merchantId;

    public function initialize()
    {
        $config = $this->app->core->config->config->toArray();
        $this->_error = $config['error_message'];
        //验证用户是否登录
        $this->_userId = $this->app->tencent->api->UserApi()->getUserId();
        if(empty($this->_userId)){
            $this->resultSet->error(1010,$this->_error['unlogin']);exit;
        }
        //验证是否为商户
        $this->_merchantId = $this->app->tencent->api->UserApi()->getMerchantIdByUserId($this->_userId);
        if(empty($this->_merchantId)){
            $this->resultSet->error(1011,$this->_error['unmerchant']);exit;
        }
    }

    /**
     * 创建
     * Create action.
     * @return void
     * @Route("/create", methods="POST", name="secondadmin")
     */
    public function createAction() {
        //权限验证
        $postData = $this->request->getPost();
        $postData['user_id'] = $this->_userId;
        $postData['merchant_id'] = $this->_merchantId;
        $insertFields = $this->app->secondhand->api->Helper()->getInsertFields();
        foreach ($insertFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $insert = $this->app->secondhand->api->Helper()->createSecond($postData);
            if(empty($insert)){
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
            $data =[
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
     * @Route("/delete", methods="POST", name="secondadmin")
     */
    public function deleteAction() {
        //权限验证
        $secondId = $this->request->getPost('id');
        if(empty($secondId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->secondhand->api->Helper()->deleteSecond($secondId,$this->_userId);
           if($result){
               $data = [
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
     * @Route("/withdraw", methods="POST", name="secondadmin")
     */
    public function withdrawAction() {
        //权限验证
        $secondId = $this->request->getPost('id');
        if(empty($secondId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->secondhand->api->Helper()->withdrawSecond($secondId,$this->_userId);
           if($result){
               $data = [
                   'withdraw_success' => $result
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
     * 更新
     * Create action.
     * @return void
     * @Route("/update", methods="POST", name="secondadmin")
     */
    public function updateAction() {
        //权限验证
        $postData = $this->request->getPost();
        if(empty($postData['id'])){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        $postData['user_id'] = $this->_userId;
        $updateFields = $this->app->secondhand->api->Helper()->getInsertFields();
        foreach ($updateFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $result = $this->app->secondhand->api->Helper()->updateSecond($postData);
            if($result){
                $data = [
                    'update_success' => $result
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
     * 我发布的二手物品列表
     * Create action.
     * @return void
     * @Route("/list", methods="GET", name="secondadmin")
     */
    public function listAction() {
        //权限验证
        try{
            $data = [];
            $result = $this->app->secondhand->api->Helper()->getListByMerchantId($this->_merchantId);
            if(!empty($result)){
                $data = [
                    'secondhand_list' => $result
                ];
            }
            $ret['data'] = $data;
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($ret);
        $this->response->success($this->resultSet->toObject());
    }

}
