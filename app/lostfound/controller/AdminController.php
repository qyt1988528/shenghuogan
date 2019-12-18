<?php
namespace Secondhand\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/lostadmin", name="lostadmin")
 */
class AdminController extends Controller
{
    private $_error;

    public function initialize()
    {
        $config = $this->app->core->config->config->toArray();
        $this->_error = $config['error_message'];
    }

    /**
     * 创建
     * Create action.
     * @return void
     * @Route("/create", methods="POST", name="lostadmin")
     */
    public function createAction() {
        //权限验证
        $postData = $this->request->getPost();
        $insertFields = $this->app->lostfound->api->Helper()->getInsertFields();
        foreach ($insertFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $insert = $this->app->lostfound->api->Helper()->createSecond($postData);
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
     * @Route("/delete", methods="POST", name="lostadmin")
     */
    public function deleteAction() {
        //权限验证
        $secondId = $this->request->getPost('id');
        if(empty($secondId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->lostfound->api->Helper()->deleteSecond($secondId);
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
     * @Route("/withdraw", methods="POST", name="lostadmin")
     */
    public function withdrawAction() {
        //权限验证
        $secondId = $this->request->getPost('id');
        if(empty($secondId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
           $result = $this->app->lostfound->api->Helper()->withdrawSecond($secondId);
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
     * @Route("/update", methods="POST", name="lostadmin")
     */
    public function updateAction() {
        //权限验证
        $postData = $this->request->getPost();
        if(empty($postData['id'])){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        $updateFields = $this->app->lostfound->api->Helper()->getInsertFields();
        foreach ($updateFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $result = $this->app->lostfound->api->Helper()->updateSecond($postData);
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

}
