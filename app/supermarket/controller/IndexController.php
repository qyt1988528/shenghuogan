<?php
namespace Supermarket\Controller;
use MDK\Controller;
use function Qiniu\waterImg;


/**
 * index controller.
 * @RoutePrefix("/supermarket", name="supermarket")
 */
class IndexController extends Controller
{
    private $_error;

    public function initialize()
    {
        $config = $this->app->core->config->config->toArray();
        $this->_error = $config['error_message'];
    }
    /**
     * Index action.
     * @return void
     * @Route("/", methods="GET", name="supermarket")
     */
    public function indexAction() {
        $page = 1;
        //分页

        $supermaketImg = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0ODgwMA==/7eb84e00-dc47-44d4-be86-74d017129daa.jpg';
        //id=1,page=1
        $supermaketData = [
            'type_list' => [
                [
                    'title' => '全部',
                    'id' => 1,
                    'selected' => true,
                ],
                [
                    'title' => '精选水果',
                    'id' => 2,
                    'selected' => false,
                ],
                [
                    'title' => '休闲食品',
                    'id' => 3,
                    'selected' => false,
                ],
                [
                    'title' => '酒水乳饮',
                    'id' => 4,
                    'selected' => false,
                ],
                [
                    'title' => '生活用品',
                    'id' => 5,
                    'selected' => false,
                ],
            ],
            'recommend_list' => [
                [
                    'title'=>'西红柿',
                    'img_url'=>$supermaketImg,
                    'base_uri' => '/supermarket/detail',
                    'id'=>1,
                    'original_price' => '¥15.89',
                    'current_price' => '¥11.89',
                ],
                [
                    'title'=>'茄子',
                    'img_url'=>$supermaketImg,
                    'base_uri' => '/supermarket/detail',
                    'id'=>2,
                    'original_price' => '¥10.70',
                    'current_price' => '¥6.00',
                ],
                [
                    'title'=>'鸡蛋',
                    'img_url'=>$supermaketImg,
                    'base_uri' => '/supermarket/detail',
                    'id'=>3,
                    'original_price' => '¥20.00',
                    'current_price' => '¥14.99',
                ],
            ],
            'total_list' => [
                [
                    'id' => 1,
                    'img_url'=>$supermaketImg,
                    'base_uri' => '/supermarket/detail',
                    'title' => '西红柿',
                    'specs' => '2kg',
                    'current_price' => '¥11.89',
                ],
                [
                    'id' => 2,
                    'img_url'=>$supermaketImg,
                    'base_uri' => '/supermarket/detail',
                    'title' => '茄子',
                    'specs' => '1kg',
                    'current_price' => '¥6.00',
                ],
                [
                    'id' => 1,
                    'img_url'=>$supermaketImg,
                    'title' => '西红柿',
                    'specs' => '2kg',
                    'current_price' => '¥11.89',
                ],
                [
                    'id' => 2,
                    'img_url'=>$supermaketImg,
                    'base_uri' => '/supermarket/detail',
                    'title' => '茄子',
                    'specs' => '1kg',
                    'current_price' => '¥6.00',
                ],
                [
                    'id' => 1,
                    'img_url'=>$supermaketImg,
                    'title' => '西红柿',
                    'specs' => '2kg',
                    'current_price' => '¥11.89',
                ],
                [
                    'id' => 2,
                    'img_url'=>$supermaketImg,
                    'base_uri' => '/supermarket/detail',
                    'title' => '茄子',
                    'specs' => '1kg',
                    'current_price' => '¥6.00',
                ],
                [
                    'id' => 1,
                    'img_url'=>$supermaketImg,
                    'base_uri' => '/supermarket/detail',
                    'title' => '西红柿',
                    'specs' => '2kg',
                    'current_price' => '¥11.89',
                ],
                [
                    'id' => 2,
                    'img_url'=>$supermaketImg,
                    'base_uri' => '/supermarket/detail',
                    'title' => '茄子',
                    'specs' => '1kg',
                    'current_price' => '¥6.00',
                ],
            ],
        ];
        $data = $supermaketData;

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
            $this->resultSet->error(1001,$this->_error['invalid_input']);
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
            $data = $this->app->supermarket->api->Helper()->detail($goodsId);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 根据关键词搜索 商品
     * @return void
     * @Route("/search", methods="GET", name="supermarket")
     */
    public function searchAction(){
        $keywords = $this->request->getParam('keywords',null,'');
        if(empty($keywords)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
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
            $data = $this->app->supermarket->api->Helper()->search($keywords);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
     * 根据关键词搜索 商品
     * @return void
     * @Route("/specs", methods="GET", name="supermarket")
     */
    public function specsAction(){
        $config = $this->app->core->config->config->toArray();
        $data = $config['specs_unit'];
        try{
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }
}
