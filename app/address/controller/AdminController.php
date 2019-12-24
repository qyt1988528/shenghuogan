<?php
namespace Address\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/addressadmin", name="addressadmin")
 */
class AdminController extends Controller
{
    private $_error;
    private $_userId;

    public function initialize()
    {
        $config = $this->app->core->config->config->toArray();
        $this->_error = $config['error_message'];
        //验证用户是否登录
        $this->_userId = $this->app->tencent->api->UserApi()->getUserId();
        if(empty($this->_userId)){
            $this->resultSet->error(1010,$this->_error['unlogin']);exit;
        }
    }

    /**
     * 添加地址
     * Create action.
     * @return void
     * @Route("/create", methods="POST", name="addressadmin")
     */
    public function createAction() {
        //权限验证
        $postData = $this->request->getPost();
        $postData['user_id'] = $this->_userId;
        $insertFields = $this->app->address->api->Helper()->getInsertFields();
        foreach ($insertFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $insert = $this->app->address->api->Helper()->createAddress($postData);
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
     * 修改地址(将原地址删除，添加新地址)
     * Create action.
     * @return void
     * @Route("/update", methods="POST", name="addressadmin")
     */
    public function updateAction() {

    }
    /**
     * 删除地址
     * Create action.
     * @return void
     * @Route("/delete", methods="POST", name="addressadmin")
     */
    public function deleteAction() {

    }
}
