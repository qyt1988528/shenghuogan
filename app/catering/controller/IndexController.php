<?php
namespace Catering\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/catering", name="catering")
 */
class IndexController extends Controller
{

    /**
     * Index action.
     * @return void
     * @Route("/", methods="GET", name="catering")
     */
    public function indexAction() {
        $data =[];
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
     * @Route("/detail", methods="GET", name="catering")
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
     * @Route("/search", methods="GET", name="catering")
     */
    public function searchAction(){
        $keywords = $this->request->getParam('keywords',null,'');
        if($keywords){

        }
        $data = [];
        try{
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
}
