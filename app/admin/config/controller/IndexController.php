<?php

namespace Admin\Config\Controller;

use Phalcon\Mvc\Controller;
use Admin\Core\Model\CoreConfigVersion;

/**
 * Index controller.
 * @require Module checkout,admin
 * @category Maiyuan\Module
 * @package  Controller
 *
 * @RoutePrefix("/admin/system", name="pushs")
 */
class IndexController extends Controller
{
    private $_helper;
    protected function onConstruct(){
        $this->_helper = $this->app->admin->core->api->Helper();
    }
    /**
     * Module index action.
     *
     * @return void
     *
     * @Route("/update", methods={"GET"}, name="admin_config")
     */
    public function getUpdateAction(){
        $systemType = $this->request->get('systemType',null,'app');
        $params = ['currentVersion'=>$systemType.'/current/version',
            'forceVersion'=>$systemType.'/force/version',
            'description'=>$systemType.'/update/description',
            'image'=>$systemType.'/update/image'];
        foreach ($params as $key=>$value){
            $params[$key] = $this->_helper->getConfig( $value)? :'';
        }
        $versions = CoreConfigVersion::find([
            " system_type='".$systemType."'",
            "order" => "version asc"
        ]);
        $params['versions'] = $versions->toArray();
        $this->resultSet->set('data',$params)->success();
        $this->response->setJsonContent($this->resultSet->toArray())->send();
    }

    /**
     * Module index action.
     *
     * @return void
     *
     * @Route("/update", methods={"POST"}, name="admin_config")
     */
    public function saveUpdateAction(){
        try {
            $data = $this->request->getPost();
            $systemType = $this->request->get('systemType',null,'app');
            $params = ['currentVersion' => $systemType.'/current/version',
                'forceVersion' => $systemType.'/force/version',
                'description' => $systemType.'/update/description',
                'image' => $systemType.'/update/image'];
            $error = [];
            if($this->request->hasFiles()){
                $uploadApi = $this->app->admin->core->api->Uploader();
                if(!$uploadApi->upload($this->request->getUploadedFiles()[0])->error) {
                    if(!empty($data['image']))
                        $uploadApi->deleteImage($data['image']);
                    $data['image'] = $uploadApi->url;
                }else{
                    throw new \Exception($uploadApi->error, 1005);//TODO::修改错误码
                }
            }
            if(!isset($data['image'])){
                $data['image'] = '';
            }
            foreach ($params as $key => $value) {
                $this->_helper->setConfig( $value, $data[$key]);
            }
            $this->resultSet->success();
        }catch(\Exception $e){
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->response->setJsonContent($this->resultSet->toArray())->send();
    }

    /**
     * Module other action.
     *
     * @return void
     *
     * @Route("/updateversion", methods={"POST"}, name="admins")
     */
    public function saveUpdateVersionAction(){
        try {
            $data = $this->request->getPost();
            if(!empty($data['id'])){
                $version = CoreConfigVersion::findFirst($data['id']);
            }else{
                $version = new CoreConfigVersion();
            }
            if($this->request->hasFiles()){
                $uploadApi = $this->app->admin->core->api->Uploader();
                if(!$uploadApi->upload($this->request->getUploadedFiles()[0])->error) {
                    if(!empty($data['image']))
                        $uploadApi->deleteImage($data['image']);
                    $data['image'] = $uploadApi->url;
                }else{
                    throw new \Exception($uploadApi->error, 1005);//TODO::修改错误码
                }
            }
            if(!isset($data['image'])){
                $data['image'] = '';
            }
            $data['system_type'] = $this->request->get('systemType',null,'app');
            $success = $version->save($data);
            if ($success) {
                $this->resultSet->success();
                $this->resultSet->set('data',[
                    'id'=>$version->id
                ])->success();
            } else {
                $msg = parent::getModelMessage($version);
                throw new \Exception($msg, 1004);
            }
        }catch(\Exception $e){
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->response->setJsonContent($this->resultSet->toArray())->send();
    }

    /**
     * @Route("/updateversion", methods={"DELETE"}, name="admins")
     */
    public function deleteVersionAction()
    {
        $this->app->admin->core->api->Helper()->deleteRecord('Admin\User\Model\CoreConfigVersion');
        $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
    }

    /**
     * all single config set 
     * @Route("/config/single", methods={"POST"}, name="admins")
     */
    public function setsingleconfigAction()
    {
        $key = $this->request->getParam('key');
        $value = $this->request->getParam('value');
        $this->_helper->setConfig( $key, $value);
        $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
    }


    /**
     * all single config get
     * @Route("/config/single", methods={"GET"}, name="admins")
     */
    public function getsingleconfigAction()
    {
        $key = $this->request->getParam('key');
        $value = $this->_helper->getConfig( $key);
        $this->response->setJsonContent($this->resultSet->success()->set('data',$value)->toArray())->send();
    }

    /**
     * all multiple config set 
     * @Route("/config/multiple", methods={"POST"}, name="admins")
     */
    public function semultipleconfigAction()
    {
        $key = $this->request->getParam('key');
        $value = $this->request->getParams()->toArray();
        $value = json_encode($value);
        $this->_helper->setConfig( $key, $value);
        $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
    }


    /**
     * all multiple config get
     * @Route("/config/multiple", methods={"GET"}, name="admins")
     */
    public function getmultipleconfigAction()
    {
        $key = $this->request->getParam('key');
        $value = $this->_helper->getConfig( $key);
        $value = json_decode($value,true);
        $this->response->setJsonContent($this->resultSet->success()->set('data',$value)->toArray())->send();
    }
}

