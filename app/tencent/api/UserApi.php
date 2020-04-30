<?php

namespace Tencent\Api;

use MDK\Api;
use Tencent\Model\User;

class UserApi extends Api
{

    const SECRET = '^&*IUgHGJoiJLYGUYUiyuigOl';
    const EXPIRES = 432000;//5天
    // const EXPIRES = 2592000;//30天
    private $_config;
    private $_model;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new User();
    }

    public function getUserId()
    {
        $userId = 0;
        $token = $this->getTokenByHeader();
        if (!empty($token)) {
            $userId = $this->getUserIdByToken($token);
        }
        return $userId;
    }
    public function getMerchantIdByUserId($userId)
    {
        if(empty($userId)){
            return 0;
        }
        $merchant = $this->detail($userId);
        $merchantId = 0;
        if (!empty($merchant)) {
            $merchantId = $merchant->merchant_id ?? 0;
        }
        return $merchantId;
    }

    public function getPlatformIdByUserId($userId)
    {
        if(empty($userId)){
            return 0;
        }
        $platform = $this->detail($userId);
        $platformId = 0;
        if (!empty($platform)) {
            $platformId = $platform->merchant_id ?? 0;
            /*
            if($platformId != -1){
                $platformId = 0;
            }
            */
        }
        return $platformId;
    }

    public function getInsertFields()
    {
        return [
            'nickname',
            'openid',
            // 'avatar_url',
            // 'gender',
            // 'language',
            // 'country',
            // 'province',
            // 'city',
        ];
    }

    public function getDefaultInsertFields($postData, $update = false)
    {
        if (!$update) {
            $defaultInsertFields = [
                'create_time' => date('Y-m-d H:i:s'),
                'add_timestamp' => strtotime(date('Y-m-d')),
            ];
        }
        $defaultInsertFields['language'] = $postData['language'] ?? '';
        $defaultInsertFields['country'] = $postData['country'] ?? '';
        $defaultInsertFields['province'] = $postData['province'] ?? '';
        $defaultInsertFields['city'] = $postData['city'] ?? '';
        $defaultInsertFields['gender'] = $postData['gender'] ?? 0;
        $defaultInsertFields['avatar_url'] = $postData['avatar_url'] ?? 0;

        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }

    public function createUser($postData)
    {
        try {
            $insertData = $this->getDefaultInsertFields($postData);
            foreach ($this->getInsertFields() as $v) {
                $insertData[$v] = $postData[$v];
            }
            $model = $this->_model;
            $model->create($insertData);
            $id = 0;
            if (isset($model->id) && !empty($model->id)) {
                $id = $model->id;
                $time = time();
                $updateData = [
                    'id' => $id,
                    'key_time' => $time,
                    'access_token' => $this->makeAccessToken($id, $time),
                ];
                $this->updateUser($updateData);
            }
            return $id;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function updateUser($postData,$create)
    {
        try {
            $updateModel = $this->_model->findFirstById($postData['id']);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = $this->getDefaultInsertFields($postData, true);
            $updateData['id'] = $postData['id'];
            foreach ($this->getInsertFields() as $v) {
                $updateData[$v] = $postData[$v];
            }
            if($create){
                $keyTime = time();
                $updateData['key_time'] = $keyTime;
                $updateData['access_token'] = $this->makeAccessToken($updateData['id'], $keyTime);
            }
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteUser($ticketId)
    {
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($ticketId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $ticketId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($userId)
    {
        $condition = "id = " . $userId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $user = $this->_model->findFirst($condition);
        return $user;
    }

    private function makeAccessToken($userId, $keyTime)
    {
        $token = md5(self::SECRET . $keyTime . $userId);
        return $token;
    }
    public function getUserByOpenid($openid){
        $condition = " openid = '" . $openid."'";
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $user = $this->_model->findFirst($condition);
        return $user;
    }

    public function getInfoByOpenid($openid, $sessionKey='')
    {
        $user = $this->getUserByOpenid($openid);
        if (empty($user)) {
            //之前没有此用户的记录
            //保存openid、session_key、session_key_time
            if(!empty($sessionKey)){
                $insertData = [
                    'openid' => $openid,
                    'session_key' => $sessionKey,
                    'session_key_time' => date('Y-m-d H:i:s'),
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                $model = $this->_model;
                $model->create($insertData);
            }
            return [];
        } else {
            //之前有此用户的记录
            if (empty($user->nickname)) {//微信昵称不能为空
                //未保存用户信息
                return [
                    'access_token' => ''
                ];
            } else {
                //保存过用户信息
                return [
                    'access_token' => $user->access_token ?? ''
                ];
            }
        }
    }

    public function updateByOpenid($postData,$create=false)
    {
        if(!isset($postData['openid']) || empty($postData['openid'])){
            return false;
        }
        $user = $this->getUserByOpenid($postData['openid']);
        if(empty($user)){
            return false;
        }else{
            $postData['id'] = $user->id ?? 0;
            return $this->updateUser($postData,$create);
        }
    }


    public function getUserIdByToken($accessToken){
        if(empty($accessToken)){
            return 0;
        }
        $condition = " access_token = '" . $accessToken."'";
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $user = $this->_model->findFirst($condition);
        if(empty($user)){
            return 0;
        }else{
            //校验token是否过期
            //未过期 将有效期重新置为7天后
            $expireTime = strtotime(date("Y-m-d", strtotime("+7 day")));
            return $user->id ?? 0;
        }
    }

    public function getTokenByHeader(){
        $header = '';
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } else if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $header = $headers['Authorization'];
            }elseif(isset($headers['ACCESS_TOKEN'])){
                $header = $headers['ACCESS_TOKEN'];
            }elseif(isset($headers['access_token'])){
                $header = $headers['access_token'];
            }elseif(isset($headers['Token'])){
                $header = $headers['Token'];
            }
        }
        return $header;
    }


}