<?php

namespace Nwdn\Controller;
use MDK\Controller;


/**
 * 你我当年 controller.
 * @RoutePrefix("/nwdn", name="tencent")
 */
class IndexController extends Controller
{

    /**
     * Home action.
     * @return void
     * @Route("/task", methods="POST", name="nwdn")
     */
    public function createAction() {

        $imgLink = $this->request->getParam('imgLink');
        $imgMd5 = $this->request->getParam('imgMd5');
        $source = $this->request->getParam('source',null,'');
        try{
            $data = $this->app->nwdn->api->Helper()->createTask($imgLink,$imgMd5,$source);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());


    }

    /**
     * 图片加滤镜.
     * @return void
     * @Route("/task", methods="GET", name="nwdn")
     */
    public function gettaskAction()
    {
        $taskId = $this->request->getParam('taskid');
        try{
            $data = $this->app->nwdn->api->Helper()->getTask($taskId);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 图片加滤镜.
     * @return void
     * @Route("/userinfo", methods="GET", name="nwdn")
     */
    public function getuserinfoAction()
    {
        $taskId = $this->request->getParam('taskid');
        try{
            $data = $this->app->nwdn->api->Helper()->getUserInfo();
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * Home action.
     * @return void
     * @Route("/task/mock", methods="POST", name="nwdn")
     */
    public function createmockAction() {

        $imgLink = $this->request->getParam('imgLink');
        $imgMd5 = $this->request->getParam('imgMd5');
        try{
            $data = [
                'taskid' => 'fd40d91f03fba7ba5028e2635c8189ea',
                'input_url' => $imgLink,
                'phase' => 1,
                'create_time' => date('Y-m-d H:i:s'),
                'balance' => 99,
            ];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());


    }

    /**
     * Home action.
     * @return void
     * @Route("/task/list", methods="GET", name="nwdn")
     */
    public function tasklistAction()
    {
        $page=$this->request->getParam('page',null,1);
        try{
            $data = $this->app->nwdn->api->Helper()->getTaskList($page);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
     * Home action.
     * 创建换脸任务
     * @return void
     * @Route("/task/face", methods="POST", name="nwdn")
     */
    public function createFaceAction(){
        $sku = $this->request->getParam('sku',null,1);//sku
        $ethnicity = $this->request->getParam('ethnicity',null,'');//肤色
        $inputUrl = $this->request->getParam('input_url',null,'');//需要换脸的用户头像url
        try{
            $msg = $this->translate->_('Network Error. Please Try Again Later!');
            if(empty($sku) || empty($ethnicity) || empty($inputUrl)){
                $this->resultSet->error(1001,$msg);
            }
            $inputUrl = $inputUrl.$this->app->core->config->qiniu->qiniuThumbnailKey;
            $ethnicity = substr($ethnicity,0,3);
            $configInfo = $this->app->nwdn->config->sku->toArray();
            //$backgroundUrl = '';
            $emid = $configInfo[$sku][$ethnicity] ?? '';

            if(empty($emid)){
                $this->resultSet->error(1002,$msg);
            }
            //人脸识别
            $this->profiler->start('faceDetect');
            $faceRet = $this->app->face->api->Helper()->faceDetect(['image_url'=>$inputUrl]);
            $this->profiler->stop('faceDetect');
            //人脸个数判断
            $faceNum = $this->app->face->api->Helper()->faceNum($faceRet);
            if($faceNum != 1){
                $msg = $this->translate->_('Please make sure there is only one person in the picture.');
                $this->resultSet->error(1003,$msg);
            }
            //脸部姿态判断，抬头、旋转（平面旋转）、摇头，这3个维度均为±15°
            $headPose = $this->app->face->api->Helper()->headPose($faceRet);
            if( ($headPose['pitch_angle']==2 || $headPose['pitch_angle']==3)
                || ($headPose['roll_angle']==2 || $headPose['roll_angle']==3)
                || ($headPose['yaw_angle']==2 || $headPose['yaw_angle']==3)
            ){
                if(!empty($headPose['message'])){
                    $this->resultSet->error(1004,$headPose['message']);
                }else{
                    $this->resultSet->error(1005,$msg);
                }
            }
            //创建换脸任务
            $this->profiler->start('faceTask');
            $this->profiler->start('createFaceTask');
            $data = $this->app->nwdn->api->Helper()->createFaceTask($inputUrl,$emid);
            $this->profiler->stop('createFaceTask');
            $taskId = $data['data']['taskid'] ?? '';
            if(empty($taskId)){
                $this->resultSet->error(1006,$msg);
            }
            //根据任务ID查询换脸任务(查询20次，每次间隔1秒)
            $outputUrl = '';
            for($i=0;$i<20;$i++){
                $data = $this->app->nwdn->api->Helper()->getFaceTask($taskId);
                if(isset($data['data']['output_url']) && !empty($data['data']['output_url'])){
                    $outputUrl = $data['data']['output_url'];
                    break;
                }
                sleep(1);//1s
//                usleep(500000);//0.5s
            }
            $this->profiler->stop('faceTask');
            if(empty($outputUrl)){
                $this->resultSet->error(1007,$msg);
            }else{
                $data = [
                    'task_id' => $taskId,
                    'output_url' => $outputUrl
                ];
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
     * 获取换脸任务结果
     * @return void
     * @Route("/task/face", methods="GET", name="nwdn")
     */
    public function getfacetaskAction()
    {
        $taskId = $this->request->getParam('taskid');
        try{
            $data = $this->app->nwdn->api->Helper()->getFaceTask($taskId);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

}