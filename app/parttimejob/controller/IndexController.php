<?php
namespace Parttimejob\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/parttimejob", name="parttimejob")
 */
class IndexController extends Controller
{

    /**
     * Index action.
     * @return void
     * @Route("/", methods="GET", name="parttimejob")
     */
    public function indexAction() {
        $data = [
            [
                'title' => '',
                'location' => '',
                'id' => 1,
                'price' => 1,
                'publish_time' => 1,
            ],
            [
                'title' => '',
                'location' => '',
                'id' => 1,
                'price' => 1,
                'publish_time' => 1,
            ],
        ];

        try{
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }

    /**
     * mergeFace action.
     * 人脸融合
     * @return void
     * @Route("/mergeFace", methods="POST", name="face")
     */
    public function mergeFaceAction(){
        $imageUrl = $this->request->getParam('image_url',null,'');
        $imageBase64 = $this->request->getParam('image_base64',null,'');
        $sku = $this->request->getParam('sku',null,'');
        if( (empty($imageUrl) && empty($imageBase64)) || empty($sku)){
            $result['code'] = 101;
            $result['msg'] = $this->translate->_('Invalid input');
            $this->resultSet->error($result['code'],$result['msg']);
        }
        try{
            $params = [
                'sku' => $sku,
                'image_url' => $imageUrl,
                'image_base64' => $imageBase64,
            ];
            $data = $this->app->face->api->Helper()->mergeFacePro($params);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * faceDetect action.
     * 人脸识别
     * @return void
     * @Route("/faceDetect", methods="POST", name="face")
     */
    public function faceDetectAction(){
        $imageBase64 = $this->request->getParam('image_base64',null,'');
        $msg = $this->translate->_('Network Error. Please Try Again Later!');
        if(empty($imageBase64)){
            $this->resultSet->error(1001,$msg);
        }
        try{
            $params = [
                'image_base64' => $imageBase64,
            ];
            $data = $this->app->face->api->Helper()->faceDetect($params);
            //人数
            $faceNum = $this->app->face->api->Helper()->faceNum($data);
            //人脸模糊度
            $isBlur = $this->app->face->api->Helper()->isBlur($data);
            if($faceNum != 1){
                $code = 1;
            }else{
                if($isBlur){
                    $code = 2;
                }else{
                    $code = 3;
                }
            }
            $data = [
                'faceNum' => (int)$faceNum,
                'detectCode' => $code,
                'isBlur' => (int)$isBlur
            ];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
}
