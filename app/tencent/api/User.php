<?php
namespace Ticket\Api;

use MDK\Api;
use Tencent\Model\User as UserModel;

class User extends Api
{

    const SECRET = '^&*IUgHGJoiJLYGUYUiyuigOl';
    const EXPIRES = 432000;//5天
    // const EXPIRES = 2592000;//30天
    private $_config;
    private $_model;
    public function __construct() {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new UserModel();
    }

    public function getUserInfo(){
        $header = '';
        if(isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } else if(isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } else if(function_exists('getallheaders')) {
            $headers = getallheaders();
            if(isset($headers['Authorization'])) {
                $header = $headers['Authorization'];
            }
        }
        if(empty($header)){

        }
        $condition = "access_token = ".$header;
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);

    }

    public function getInsertFields(){
        return $insertFields = [
            'avatar_url',
            'nickname',
            'gender',
            // 'language',
            // 'country',
            // 'province',
            // 'city',
        ];
    }
    public function getDefaultInsertFields($postData){
        $defaultInsertFields = [
            'create_time' => date('Y-m-d H:i:s'),
        ];
        $defaultInsertFields['language'] = $postData['language'] ?? '';
        $defaultInsertFields['country'] = $postData['country'] ?? '';
        $defaultInsertFields['province'] = $postData['province'] ?? '';
        $defaultInsertFields['city'] = $postData['city'] ?? '';
        $defaultInsertFields['access_token'] = '';

        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }
    public function createUser($postData){
        try{
            $insertData = $this->getDefaultInsertFields($postData);
            foreach ($this->getInsertFields() as $v){
                $insertData[$v] = $postData[$v];
            }
            $model = $this->_model;
            $model->create($insertData);
            $id = 0;
            if(isset($model->id) && !empty($model->id)){
                $id = $model->id;
                $time = time();
                $updateData = [
                    'id' => $id,
                    'key_time' => $time,
                    'access_token' => $this->makeAccessToken($id,$time),
                ];
                $this->updateUser($updateData);
            }
            return $id;
        }catch (\Exception $e){
            return 0;
        }
    }
    public function updateUser($postData){
        try{
            $updateData = ['id' => $postData['id']];
            $updateModel = $this->_model->findFirstById($postData['id']);
            if(empty($updateModel)){
                return false;
            }
            foreach ($this->getInsertFields() as $v){
                $updateData[$v] = $postData[$v];
            }
            $updateModel->update($updateData);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

    public function deleteUser($ticketId){
        try{
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($ticketId);
            if(empty($updateModel)){
                return false;
            }
            $updateData = [
                'id' => $ticketId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    public function detail($ticketId){
        $condition = "id = ".$ticketId;
        $condition .= " and status = ".$this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }

    private function makeAccessToken($userId,$keyTime){
        $token = md5( self::SECRET . $keyTime. $userId );
        return $token;
    }

    public function get(){

    }




}