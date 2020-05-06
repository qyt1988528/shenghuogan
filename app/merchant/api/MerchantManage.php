<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/4/30
 * Time: 下午2:52
 */
namespace Merchant\Api;

use MDK\Api;
use Merchant\Model\Merchant;

class MerchantManage extends Api
{
    private $_config;
    private $_model;
    private $_merchantStatus;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_merchantStatus = $this->app->core->config->merchant->toArray();
        $this->_model = new Merchant();
    }

    public function getInsertFields()
    {
        return $insertFields = [
            'name',
            'cellphone',
            'image_identity_card',
            'image_business_license',
        ];
    }

    public function getDefaultInsertFields($postData)
    {
        $defaultInsertFields = [
            'code' => $this->getMerchantCode(),
            'business_status' => $this->_merchantStatus['business_status']['auditing']['code'],
            'create_time' => date('Y-m-d H:i:s'),
            'last_operate_user_id' => $postData['user_id'] ?? 0,
        ];
        //update_time、status采用默认值
        return $defaultInsertFields;
    }

    public function createMerchant($postData)
    {
        try {
            $insertData = $this->getDefaultInsertFields($postData);
            foreach ($this->getInsertFields() as $v) {
                $insertData[$v] = $postData[$v];
            }
            $model = $this->_model;
            $model->create($insertData);
            return !empty($model->id) ? $model->id : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function updateMerchant($postData)
    {
        try {
            $updateData = ['id' => $postData['id']];
            $updateModel = $this->_model->findFirstById($postData['id']);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($postData['id'],$postData['user_id']);
            if($judgeResult == false){
                return false;
            }
            foreach ($this->getInsertFields() as $v) {
                $updateData[$v] = $postData[$v];
            }
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    //下架
    public function withdrawMerchant($merchantId,$userId)
    {
        try {
            $updateModel = $this->_model->findFirstById($merchantId);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($merchantId,$userId);
            if($judgeResult == false){
                return false;
            }
            $updateData = [
                'id' => $merchantId,
                'business_status' => $this->_merchantStatus['business_status']['closing']['code'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    //上架-营业中
    public function unwithdrawMerchant($merchantId,$userId)
    {
        try {
            $updateModel = $this->_model->findFirstById($merchantId);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($merchantId,$userId);
            if($judgeResult == false){
                return false;
            }
            $updateData = [
                'id' => $merchantId,
                'business_status' => $this->_merchantStatus['business_status']['opening']['code'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteMerchant($merchantId,$userId)
    {
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($merchantId);
            if (empty($updateModel)) {
                return false;
            }
            $judgeResult = $this->judgeUser($merchantId,$userId);
            if($judgeResult == false){
                return false;
            }
            $updateData = [
                'id' => $merchantId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($merchantId,$userId=0)
    {
        $condition = "id = " . $merchantId;
        $judgeResult = $this->judgeUser($merchantId,$userId);
        if($judgeResult == false){
            return false;
        }
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition)->toArray();
        if(!empty($goods)){
            $goods['business_status_description'] = $this->getBusinessDescription($goods['business_status']);
        }
        return $goods;
    }
    public function detailByCellphone($cellphone)
    {
        $condition = "cellphone = " . $cellphone;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }


    public function getList($keywords='',$page = 1, $pageSize = 20)
    {
        $keywords = trim($keywords);
        $keywords = str_replace('%','',$keywords);
        $keywords = str_replace(' ','',$keywords);
        $start = ($page - 1) * $pageSize;
        if(!empty($keywords)){
            $merchants = $this->modelsManager->createBuilder()
                ->columns('*')
                ->from(['sg' => 'Merchant\Model\Merchant'])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->andWhere('sg.name like :goodsName: or sg.cellphone like :goodsName:', ['goodsName' => '%' . $keywords . '%'])
                ->limit($start, $pageSize)
                ->getQuery()
                ->execute()
                ->toArray();
        }else{
            $merchants = $this->modelsManager->createBuilder()
                ->columns('*')
                ->from(['sg' => 'Merchant\Model\Merchant'])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->limit($start, $pageSize)
                ->getQuery()
                ->execute()
                ->toArray();
        }
        if(!empty($merchants)){
            foreach ($merchants as &$v){
                $v['business_status_description'] = $this->getBusinessDescription($v['business_status']);
            }

        }
        return $merchants;
    }



    public function judgeUser($id, $userId)
    {
        $condition = " id = " . $id;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        if (!empty($goods)) {
            return true;
            //判断是平台用户
            if (isset($goods->user_id) && $goods->user_id == $userId) {
                return true;
            } else {
                //查询是否同一个商户不同用户
                $user = $this->app->tencent->api->UserApi()->detail($userId);
                if (!empty($user)) {
                    if (isset($user->merchant_id) && isset($goods->merchant_id) && $user->merchant_id == $goods->merchant_id) {
                        return true;
                    } else {
                        return false;

                    }
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }

    }

    public function getMerchantCode(){
        $s = 'SH';
        $date = date('ymd');
        $r1 = mt_rand(10,99);
        $r2 = mt_rand(10,99);
        $code = $s.$date.$r1.$r2;
        return $code;
    }

    public function getBusinessDescription($businessStatus){
        $description = '';
        $businessStatusArr = $this->_merchantStatus['business_status'];
        foreach ($businessStatusArr as $v){
            if($v['code'] == $businessStatus){
                $description = $v['title'];
                break;
            }
        }
        return $description;

    }


}