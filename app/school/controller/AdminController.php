<?php
namespace School\Controller;
use MDK\Controller;


/**
 * Admin controller.
 * @RoutePrefix("/schooladmin", name="schooladmin")
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
     * 创建缴费申请
     * Create action.
     * @return void
     * @Route("/create", methods="POST", name="schooladmin")
     */
    public function createAction() {
        //权限验证
        $postData = $this->request->getPost();
        $postData['user_id'] = $this->_userId;
        $insertFields = $this->app->school->api->Helper()->getInsertFields();
        foreach ($insertFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $insert = $this->app->school->api->Helper()->createRecord($postData);
            if(empty($insert)){
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
            $data['data'] =[
                'id' => $insert
            ];
            $sendData = [
                'goods_id' => $insert,
                'goods_type' => 'school',
                'goods_num' => $postData['num'] ?? 1
            ];
            $goodsData = [$sendData];
            $addressId = 0;
            $couponNo = '';
            $orderSchool = $this->app->order->api->Helper()->createOrder($goodsData, $this->_userId, $addressId, $couponNo);
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
     * @Route("/delete", methods="POST", name="school")
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
     * @Route("/withdraw", methods="POST", name="school")
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
     * @Route("/update", methods="POST", name="school")
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
    /**
     * 我发布的缴费申请
     * Create action.
     * @return void
     * @Route("/list", methods="GET", name="school")
     */
    public function listAction() {
        //权限验证
        try{
            $data = [];
            $result = $this->app->parttimejob->api->Helper()->getListByUserId($this->_userId);
            if(!empty($result)){
                $data['data'] = [
                    'pay_list' => $result
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
