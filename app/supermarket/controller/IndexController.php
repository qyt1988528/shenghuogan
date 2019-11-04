<?php
namespace Supermarket\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/supermarket", name="supermarket")
 */
class IndexController extends Controller
{

    /**
     * Index action.
     * @return void
     * @Route("/", methods="GET", name="supermarket")
     */
    public function indexAction() {
        $page = 1;
        //分页
        $data = [
            'type_list' => [
                [
                    'id' => '全部',
                    'title' => '',
                    'selected' => '',
                ],
                [
                    'id' => '精选水果',
                    'title' => '',
                    'selected' => '',
                ],
                [
                    'id' => '休闲食品',
                    'title' => '',
                    'selected' => '',
                ],
                [
                    'id' => '酒水乳饮',
                    'title' => '',
                    'selected' => '',
                ],
                [
                    'id' => '生活用品',
                    'title' => '',
                    'selected' => '',
                ],
            ],
            'recommend_list' => [
                [
                    'title'=>'快递',
                    'img_url'=>'',
                    'id'=>10,
                    'original_price' => '',
                    'current_price' => '',
                ],
                [
                    'title'=>'快递',
                    'img_url'=>'',
                    'id'=>10,
                    'original_price' => '',
                    'current_price' => '',
                ],
                [
                    'title'=>'快递',
                    'img_url'=>'',
                    'id'=>10,
                    'original_price' => '',
                    'current_price' => '',
                ],
            ],
            'total_list' => [
                [
                    'id' => 1,
                    'title' => '',
                    'spec' => '',
                    'price' => ''
                ],
                [
                    'id' => 1,
                    'title' => '',
                    'spec' => '',
                    'price' => ''
                ],

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
     * 商品详情
     * @return void
     * @Route("/detail", methods="GET", name="supermarket")
     */
    public function detailAction(){
        $goodsId = $this->request->getParam('id',null,'');
        if(empty($goodsId)){
            $this->resultSet->error(1001,'invalid input!');
        }
        $data = [
            'banner' => [],//几张图?
            'title' => '',
            'id' => 1,
            'price' => '',
            'desc' => '',
            'count' => '',//库存
        ];
        try{
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * faceDetect action.
     * 根据关键词搜索 商品
     * @return void
     * @Route("/search", methods="POST", name="supermarket")
     */
    public function searchAction(){
        $keywords = $this->request->getParam('keywords',null,'');
        if(empty($keywords)){

        }
        //考虑拼音搜索
        $data = [
            [
                'id' => 1,
                'title' => '',
                'spec' => '',
                'price' => ''
            ]

        ];
        try{
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
}
