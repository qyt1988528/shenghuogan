<?php
namespace Admin\Core\Api;
use Admin\Core\Model\CoreConfigDataTest;
use MDK\Api;

class HelperTest extends Api
{
    public function __construct() {

    }

    /**
     * get model error message
     * @param $model
     * @return string
     */
    public function getModelMessage($model)
    {
        $msg ='';
        foreach ($model->getMessages() as $message) {
            $msg.=$message->getMessage().',';
        }
        return $msg;
    }

    /**
     * 删除模型的记录
     * @param $modelName
     * @return $this
     */
    public function deleteRecord($modelName)
    {
        $id = $this->request->getParam('id');
        $model = $modelName::findFirstById($id);
        if($model){
            if(!$model->delete()){
                $msg =$this->getModelMessage($model);
                $this->resultSet->error(1002,$msg);
            }
        }else{
            $this->resultSet->error(1005,'no record');
        }
        return $this;
    }

    /**
     * 获取站点配置信息
     * @param $path
     * @return mixed
     */
    public function getConfig($path){
        $config = CoreConfigDataTest::findFirst(
            [
                "path = '{$path}' ",
            ]
        );
        if($config){
            $result = $config->value;
        }else{
            $result = false;
        }
        return $result;
    }



    /**
     * @param $path 路径
     * @param $value 值
     * @return mixed true
     */
    public function setConfig($path,$value)
    {
        $config = CoreConfigDataTest::findFirst([" path = '{$path}'"]);
        if(!$config){
            $config = new CoreConfigDataTest();
        }
        $config->path = $path;
        $config->value = $value;
        $success = $config->save();
        if(!$success){
            return false;
        }
        return $success;
    }

    public function test(){
        /*
         * $phql = "SELECT c.* FROM Cars AS c ORDER BY c.name";

$cars = $manager->executeQuery($phql);

foreach ($cars as $car) {
    echo "Name: ", $car->name, "\n";
}
         */
    }
}