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

    public function initialize()
    {
        $config = $this->app->core->config->config->toArray();
        $this->_error = $config['error_message'];
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
           $result = $this->app->parttimejob->api->Helper()->deleteParttimejob($parttimejobId);
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
           $result = $this->app->parttimejob->api->Helper()->withdrawParttimejob($parttimejobId);
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
    
}
