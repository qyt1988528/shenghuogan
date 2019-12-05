<?php

namespace Parttimejob\Api;

use MDK\Api;
use Parttimejob\Model\Parttimejob;

class Helper extends Api
{
    private $_config;
    private $_model;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new Parttimejob();
    }

    public function getInsertFields()
    {
        return $insertFields = [
            'title',
            'description',
            'commission',
            'location',
            'cellphone',
            'qq',
            'wechat',
        ];
    }

    public function getDefaultInsertFields($postData)
    {
        $defaultInsertFields = [
            'is_hiring' => $this->_config['hiring_status']['hiring'],
            'create_time' => date('Y-m-d H:i:s'),
            'publish_time' => date('Y-m-d H:i:s'),
        ];
        if (!empty($postData['title'])) {
            $defaultInsertFields['title_pinyin'] = $this->app->core->api->Pinyin()->getpy($postData['title']);
        }
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }

    public function createParttimejob($postData)
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

    public function updateParttimejob($postData)
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

    //下架
    public function withdrawParttimejob($parttimejobId)
    {
        try {
            $updateModel = $this->_model->findFirstById($parttimejobId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $parttimejobId,
                'is_hiring' => $this->_config['hiring_status']['unhiring'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteParttimejob($parttimejobId)
    {
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($parttimejobId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $parttimejobId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($parttimejobId)
    {
        $condition = "id = " . $parttimejobId;
        $condition .= " and is_hiring = " . $this->_config['hiring_status']['hiring'];
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
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
            ->columns('*')
            ->from(['sg' => 'Parttimejob\Model\Parttimejob'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->andWhere('sg.title like :goodsName: ', ['goodsName' => '%' . $goodsName . '%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();
        return $goods;
    }

    public function parttimejobList(){
         $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => 'Parttimejob\Model\Parttimejob'])
            ->where('sg.is_hiring = :hiring: ', ['hiring' => $this->_config['hiring_status']['hiring']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->getQuery()
            ->execute();
        return $goods;

    }


}