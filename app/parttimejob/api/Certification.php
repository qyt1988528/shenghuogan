<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/5/1
 * Time: 下午11:11
 */
namespace Parttimejob\Api;

use MDK\Api;
use Parttimejob\Model\CertificationRecord;

class Certification extends Api
{
    private $_config;
    private $_model;
    private $_certStatus;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new CertificationRecord();
        $this->_certStatus = $this->app->core->config->certification->toArray();
    }

    public function getInsertFields()
    {
        return $insertFields = [
            'cellphone',
            'id_photo',
            'upload_user_id',
        ];
    }

    public function getDefaultInsertFields($postData)
    {
        $defaultInsertFields = [
            'certification_status' => $this->_certStatus['certification_status']['auditing']['code'],
            'create_time' => date('Y-m-d H:i:s'),
        ];
        //其他字段update_time、status采用默认值
        return $defaultInsertFields;
    }

    public function createRecord($postData)
    {
        try {
            $insertData = $this->getDefaultInsertFields($postData);
            foreach ($this->getInsertFields() as $v) {
                $insertData[$v] = $postData[$v];
            }
            //创建之前校验是否存在 待审核 或 已通过 存在则不创建
            //未通过的要置status为-1
            $model = $this->_model;
            $model->create($insertData);
            return !empty($model->id) ? $model->id : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function updateRecord($postData)
    {
        try {
            $updateData = ['id' => $postData['id']];
            $updateModel = $this->_model->findFirstById($postData['id']);
            if (empty($updateModel)) {
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

    //更新认证状态 通过或拒绝
    public function updateCertStatus($certId,$certStatus,$auditUserId)
    {
        try {
            $updateModel = $this->_model->findFirstById($certId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $certId,
                'certification_status' => $certStatus,
                'audit_user_id' => $auditUserId,
                'audit_time' => date('Y-m-d H:i:s'),
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteRecord($certId)
    {
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($certId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $certId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($certId)
    {
        $condition = "id = " . $certId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $certData = $this->_model->findFirst($condition);
        return $certData;
    }
    public function detailByUserId($userId)
    {
        $condition = "upload_user_id = " . $userId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        // $condition .= " order by id desc limit 1";
        $certData = $this->_model->findFirst($condition);//->toArray();
        if(!empty($certData)){
            $certData = $certData->toArray();
            $certData['certification_status_description'] = $this->getCertificationDescription($certData['certification_status']);
        }
        return $certData;
    }
    public function addTotalField($obj){
        $arr = [];
        if(!empty($obj)){
            foreach ($obj as $k=>$v){
                $arr[$k] = $v;
            }
            $arr['total_views'] = ($arr['views'] ?? 0) + ($arr['base_views'] ?? 0);
        }
        return (object)$arr;
    }


    public function search($goodsName)
    {
        /*
        $slideAds = $this->modelsManager->createBuilder()
            ->columns('cts.image_ratio,cts.image,cts.jump_url,cts.title,cts.ga_name,vc.condition')
            ->from(['cts'=>'Supermarket\Model\SupermarketGoods'])
            ->join('Core\Model\VersionControl','vc.table_id = cts.id and vc.table_name="cms_home_top_slide"','vc','LEFT')
            ->where('is_selling = :selling: and status = :valid: and title like :goodsName:',['store'=>$store])
            ->andWhere('b.store = :store: AND b.active=1 and platforms like :systemType:',['store'=>$store,'systemType' => '%'.$systemType.'%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();*/
        $goods = $this->modelsManager->createBuilder()
            ->columns('id,user_id,title,description,location,commission,cellphone,qq,wechat,is_hiring,publish_time,end_time,views,base_views,sort,goods_type,status,(views+base_views) as total_views')
            ->from(['sg' => 'Parttimejob\Model\Parttimejob'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->andWhere('sg.title like :goodsName: ', ['goodsName' => '%' . $goodsName . '%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();
        return $goods;
    }


    /**
     * @param string $keywords 此处为手机号 仅显示 待审核 和 已通过的
     * @param int $page
     * @param int $pageSize
     * @return mixed
     */
    public function getList($keywords='',$page = 1, $pageSize = 20)
    {
        $keywords = trim($keywords);
        $keywords = str_replace('%','',$keywords);
        $keywords = str_replace(' ','',$keywords);
        $start = ($page - 1) * $pageSize;
        if(!empty($keywords)){
            $merchants = $this->modelsManager->createBuilder()
                ->columns('*')
                ->from(['sg' => 'Parttimejob\Model\CertificationRecord'])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->andWhere('sg.cellphone like :goodsName:', ['goodsName' => '%' . $keywords . '%'])
                ->andWhere('sg.certification_status = :pass: or sg.certification_status = :auditing:',
                    ['pass' => $this->_certStatus['certification_status']['passed']['code'],
                        'auditing'=>$this->_certStatus['certification_status']['auditing']['code']])
                ->limit($start, $pageSize)
                ->getQuery()
                ->execute()
                ->toArray();
        }else{
            $merchants = $this->modelsManager->createBuilder()
                ->columns('*')
                ->from(['sg' => 'Parttimejob\Model\CertificationRecord'])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->andWhere('sg.certification_status = :pass: or sg.certification_status = :auditing:',
                    ['pass' => $this->_certStatus['certification_status']['passed']['code'],
                        'auditing'=>$this->_certStatus['certification_status']['auditing']['code']])
                ->limit($start, $pageSize)
                ->getQuery()
                ->execute()
                ->toArray();
        }
        if(!empty($merchants)){
            foreach ($merchants as &$v){
                $v['certification_status_description'] = $this->getCertificationDescription($v['certification_status']);
            }
        }
        return $merchants;
    }


    public function getCertificationDescription($businessStatus){
        $description = '';
        $businessStatusArr = $this->_certStatus['certification_status'];
        foreach ($businessStatusArr as $v){
            if($v['code'] == $businessStatus){
                $description = $v['title'];
                break;
            }
        }
        return $description;

    }
        //实名认证审核 通过 和 拒绝 通过后检查是否有相同手机号的商户，有则绑定
    public function tmpPassCertification($certId,$auditUserId){
        //查询该ID是否存在
        if(empty($certId)){
            return false;
        }
        $certData = $this->detail($certId);
        if(empty($certData)){
            return false;
        }
        $certData = $this->app->core->api->CheckEmpty()->newToArray($certData);
        // $certData = $certData->toArray();
        //置为通过
        $certStatus = $this->_certStatus['certification_status']['passed']['code'];
        $updateCertResult = $this->updateCertStatus($certId,$certStatus,$auditUserId);
        if(empty($updateCertResult)){
            return false;
        }
        //查询是否有相同手机号的商户 有则绑定
        // var_dump($certData['cellphone']);exit;
        $merchantData = $this->app->merchant->api->MerchantManage()->detailByCellphone($certData['cellphone']);
        if(!empty($merchantData)){
            $merchantData = $merchantData->toArray();
            //绑定商户
            $userId = $certData['upload_user_id'];
            $merchantId = $merchantData['id'];
            $this->app->tencent->api->UserApi()->bindMerchant($userId, $merchantId);
        }
        return true;
    }

        //实名认证不通过
    public function tmpRefuseCertification($certId,$auditUserId){
        //查询该ID是否存在
        if(empty($certId)){
            return false;
        }
        $certData = $this->detail($certId);
        if(empty($certData)){
            return false;
        }
        //置为拒绝
        $certStatus = $this->_certStatus['certification_status']['refused']['code'];
        return $this->updateCertStatus($certId,$certStatus,$auditUserId);
    }

}
