<?php

namespace School\Api;

use MDK\Api;
use School\Model\School;


class Helper extends Api
{
    private $_config;
    private $_model;
    private $_certStatus;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new School();
        $this->_certStatus = $this->app->core->config->certification->toArray();
    }

    public function getInsertFields()
    {
        return $insertFields = [
            'name',
            'stu_id_num',
            'id_num',
            'amount',
            // 'cellphone',
            'user_id',
        ];
    }

    public function getDefaultInsertFields($postData)
    {
        $defaultInsertFields = [
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

    //更新成支付状态
    public function updatePayStatus($schoolId, $platformUserId)
    {
        try {
            $updateModel = $this->_model->findFirstById($schoolId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $schoolId,
                'pay_status' => 1,
                'platform_user_id' => $platformUserId,
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


    //普通用户需要查询缴费记录
    //平台用户需要查询待缴费的记录

    /**
     * @param string $keywords 此处为手机号 仅显示 待审核 和 已通过的
     * @param int $page
     * @param int $pageSize
     * @return mixed
     */
    public function getList($payStatus = 0, $userId = 0, $page = 1, $pageSize = 20)
    {
        $start = ($page - 1) * $pageSize;
        if (!empty($userId)) {
            //传userId 为用户查询
            $merchants = $this->modelsManager->createBuilder()
                ->columns('*')
                ->from(['sg' => 'School\Model\School'])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->andWhere('sg.pay_status = :payStatus:', ['payStatus' => $payStatus])
                ->limit($start, $pageSize)
                ->getQuery()
                ->execute()
                ->toArray();
        } else {
            //未传userId 为平台查询
            $merchants = $this->modelsManager->createBuilder()
                ->columns('*')
                ->from(['sg' => 'Parttimejob\Model\CertificationRecord'])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->andWhere('sg.certification_status = :pass: or sg.certification_status = :auditing:',
                    ['pass' => $this->_certStatus['certification_status']['passed']['code'],
                        'auditing' => $this->_certStatus['certification_status']['auditing']['code']])
                ->limit($start, $pageSize)
                ->getQuery()
                ->execute()
                ->toArray();
        }
        if (!empty($merchants)) {
            foreach ($merchants as &$v) {
                $v['certification_status_description'] = $this->getCertificationDescription($v['certification_status']);
            }
        }
        return $merchants;
    }


    //获取缴费描述
    public function getPayDescription($businessStatus)
    {
        $description = '';
        $businessStatusArr = $this->_certStatus['certification_status'];
        foreach ($businessStatusArr as $v) {
            if ($v['code'] == $businessStatus) {
                $description = $v['title'];
                break;
            }
        }
        return $description;

    }

}
