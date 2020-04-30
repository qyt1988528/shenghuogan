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

}
