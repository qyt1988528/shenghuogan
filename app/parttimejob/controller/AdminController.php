<?php
namespace Parttimejob\Controller;
use MDK\Controller;


/**
 * Admin controller.
 * @RoutePrefix("/parttimejobadmin", name="parttimejobadmin")
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
     * @Route("/create", methods="POST", name="parttimejobadmin")
     */
    public function createAction() {
        //权限验证
        $postData = $this->request->getPost();
        $postData['user_id'] = $this->_userId;
        $postData['merchant_id'] = $this->_merchantId;
        $insertFields = $this->app->parttimejob->api->Helper()->getInsertFields();
        foreach ($insertFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $insert = $this->app->parttimejob->api->Helper()->createParttimejob($postData);
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
     * @Route("/delete", methods="POST", name="parttimejobadmin")
     */
    public function deleteAction() {
        //权限验证
        $parttimejobId = $this->request->getPost('id');
        if(empty($parttimejobId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->parttimejob->api->Helper()->deleteParttimejob($parttimejobId,$this->_userId);
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
     * @Route("/withdraw", methods="POST", name="parttimejobadmin")
     */
    public function withdrawAction() {
        //权限验证
        $parttimejobId = $this->request->getPost('id');
        if(empty($parttimejobId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->parttimejob->api->Helper()->withdrawParttimejob($parttimejobId,$this->_userId);
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
     * @Route("/update", methods="POST", name="parttimejobadmin")
     */
    public function updateAction() {
        //权限验证
        $postData = $this->request->getPost();
        if(empty($postData['id'])){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        $postData['user_id'] = $this->_userId;
        $updateFields = $this->app->parttimejob->api->Helper()->getInsertFields();
        foreach ($updateFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $result = $this->app->parttimejob->api->Helper()->updateParttimejob($postData);
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
     * 我发布的兼职列表
     * Create action.
     * @return void
     * @Route("/list", methods="GET", name="parttimejobadmin")
     */
    public function listAction() {
        //权限验证
        try{
            $data = [];
            $result = $this->app->parttimejob->api->Helper()->getListByUserId($this->_userId);
            if(!empty($result)){
                $data = [
                    'parttimejob_list' => $result
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
