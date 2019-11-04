<?php
namespace Home\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/home", name="home")
 */
class IndexController extends Controller
{

    /**
     * Index action.
     * @return void
     * @Route("/", methods="GET", name="home")
     */
    public function indexAction() {
        $tmpData = [
            'cover' => '',
            'icon' => [
                [
                    'title'=>'超市',
                    'img_url'=>'',
                    'id'=>1,
                ],
                [
                    'title'=>'兼职',
                    'img_url'=>'',
                    'id'=>2,
                ],
                [
                    'title'=>'门票',
                    'img_url'=>'',
                    'id'=>3,
                ],
                [
                    'title'=>'住宿',
                    'img_url'=>'',
                    'id'=>4,
                ],
                [
                    'title'=>'餐饮',
                    'img_url'=>'',
                    'id'=>5,
                ],
                [
                    'title'=>'校园网',
                    'img_url'=>'',
                    'id'=>6,
                ],
                [
                    'title'=>'租房',
                    'img_url'=>'',
                    'id'=>7,
                ],
                [
                    'title'=>'租车',
                    'img_url'=>'',
                    'id'=>8,
                ],
                [
                    'title'=>'二手物',
                    'img_url'=>'',
                    'id'=>9,
                ],
                [
                    'title'=>'快递',
                    'img_url'=>'',
                    'id'=>10,
                ],
            ],
            'ad' => [
                [
                    'title' => '失物招领',
                    'desc' => '找回您失去的爱',
                    'id' =>  1,
                ],
                [
                    'title' => '驾考报名',
                    'desc' => '全线优质驾校',
                    'id' =>  2,
                ]
            ],
            'recommend_list' => [
                [
                    'title'=>'快递',
                    'img_url'=>'',
                    'id'=>10,
                    'type' => '',
                    'price' => '',
                ],
                [
                    'title'=>'快递',
                    'img_url'=>'',
                    'id'=>10,
                    'type' => '',
                    'price' => '',

                ],
                [
                    'title'=>'快递',
                    'img_url'=>'',
                    'id'=>10,
                    'type' => '',
                    'price' => '',
                ],
            ],
            'part_time_job_list' => [
                [
                    'title'=>'快递',
                    'location'=>'',
                    'id'=>10,
                    'publish_time' => '',
                    'price' => '',
                ],
                [
                    'title'=>'快递',
                    'location'=>'',
                    'id'=>10,
                    'publish_time' => '',
                    'price' => '',
                ],
                [
                    'title'=>'快递',
                    'location'=>'',
                    'id'=>10,
                    'publish_time' => '',
                    'price' => '',
                ],
            ],
            'life_info_list' => [
                [

                    'title'=>'快递',
                    'img_url'=>'',
                    'id'=>10,
                    'type' => '',
                    'price' => '',
                    'publish_time' => '',
                ],
                [
                    'title'=>'快递',
                    'img_url'=>'',
                    'id'=>10,
                    'type' => '',
                    'price' => '',
                    'publish_time' => '',
                ],
                [
                    'title'=>'快递',
                    'img_url'=>'',
                    'id'=>10,
                    'type' => '',
                    'price' => '',
                    'publish_time' => '',
                ],
            ],
        ];

        $data['data'] = $tmpData;

        try{
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

        $data = [];
        try{

        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
}
