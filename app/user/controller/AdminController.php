<?php
namespace User\Controller;
use MDK\Controller;


/**
 * Admin controller.
 * @RoutePrefix("/useradmin", name="useradmin")
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
     * 添加实名认证信息
     * Create action.
     * @return void
     * @Route("/createCert", methods="POST", name="useradmin")
     */
    public function createCertAction() {
        //权限验证
        $postData = $this->request->getPost();
        $postData['upload_user_id'] = $this->_userId;
        try{
            $verifyPhone = $this->app->core->api->Phone()->checkPhone($postData['cellphone']);
            if (!$verifyPhone) {
                $this->resultSet->error(1001, $this->_error['cellphone']);
            }
            $insert = $this->app->user->api->Helper()->createCert($postData);
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
     * 更新实名认证信息(一般为被拒后，才会使用更新操作)
     * Update action.
     * @return void
     * @Route("/updateCert", methods="POST", name="useradmin")
     */
    public function updateAction() {
        //权限验证
        $postData = $this->request->getPost();
        if(empty($postData['id'])){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        $postData['upload_user_id'] = $this->_userId;
        try{
            $verifyPhone = $this->app->core->api->Phone()->checkPhone($postData['cellphone']);
            if (!$verifyPhone) {
                $this->resultSet->error(1002, $this->_error['cellphone']);
            }
            $result = $this->app->user->api->Helper()->updateCert($postData);
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
     * 当前实名认证信息
     * Detail action.
     * @return void
     * @Route("/detailCert", methods="GET", name="useradmin")
     */
    public function detailCertAction(){
        try{
            $result = $this->app->user->api->Helper()->detail($this->_userId);
            if(!empty($result)){
                $data['data'] = $result;
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
