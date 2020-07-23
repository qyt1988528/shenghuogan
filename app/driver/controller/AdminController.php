<?php
namespace Driver\Controller;
use MDK\Controller;


/**
 * Admin controller.
 * @RoutePrefix("/driveradmin", name="driveradmin")
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
    }

    /**
     * 创建
     * Create action.
     * @return void
     * @Route("/create", methods="POST", name="driveradmin")
     */
    public function createAction() {
        //权限验证
        $postData = $this->request->getPost();
        $postData = $this->request->getParam('driver_data');//已经是数组了
        $insertFields = $this->app->driver->api->Helper()->getInsertFields();
        foreach ($insertFields as $v){
            if(empty($postData[$v])){
                // var_dump($v);
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        $postData['user_id'] = $this->_userId;
        $postData['merchant_id'] = $this->_merchantId;
        try{
            $insert = $this->app->driver->api->Helper()->createTicket($postData);
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
     * 删除
     * Create action.
     * @return void
     * @Route("/delete", methods="POST", name="driveradmin")
     */
    public function deleteAction() {
        //权限验证
        $cateringId = $this->request->getPost('id');
        if(empty($cateringId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->driver->api->Helper()->deleteTicket($cateringId,$this->_userId);
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
     * @Route("/withdraw", methods="POST", name="driveradmin")
     */
    public function withdrawAction() {
        //权限验证
        $cateringId = $this->request->getPost('id');
        if(empty($cateringId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->driver->api->Helper()->withdrawTicket($cateringId,$this->_userId);
           if($result){
               $data['data'] = [
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
     * @Route("/update", methods="POST", name="driveradmin")
     */
    public function updateAction() {
        //权限验证
        $postData = $this->request->getPost();
        $postData = $this->request->getParam('driver_data');//已经是数组了
        if(empty($postData['id'])){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        $postData['user_id'] = $this->_userId;
        $updateFields = $this->app->driver->api->Helper()->getInsertFields();
        foreach ($updateFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $result = $this->app->driver->api->Helper()->updateTicket($postData);
            if($result){
                $data['data'] = [
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
 

}
