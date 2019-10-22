<?php

namespace Admin\Product\Controller;

use Phalcon\Mvc\Controller;
use Product\Model\ProductConfigClip;
use Phalcon\Paginator\Adapter\QueryBuilder as PaginatorQueryBuilder;

/**
 * Index controller.
 * @require Module checkout,admin
 * @category Maiyuan\Module
 * @package  Controller
 *
 * @RoutePrefix("/admin/product", name="products")
 */
class IndexController extends Controller
{
    /**
     * Home action.
     * @return void
     * @throws
     * @Route("/chartlet", methods="GET", name="products")
     */
    public function indexAction()
    {
        $builder = $this->modelsManager->createBuilder()
            ->from(['pcc'=>"Product\Model\ProductConfigClip"]);

        if($this->request->has('keyword')){
            $keyword = $this->request->get('keyword');
            $builder->where('pcc.sku like :keyword:',["keyword"=>"%".$keyword."%"]);
        }
        $paginator = new PaginatorQueryBuilder(
            [
                "builder" => $builder,
                "limit"   => $this->request->get('limit',null,15),
                "page"    => $this->request->get('page',null,1),
            ]
        );
        $data['list']['data'] = $paginator->getPaginate()->items->toArray();
        $data['total'] = $paginator->getPaginate()->total_items;
        $this->resultSet->set('data',$data)->success();
        $this->response->setJsonContent($this->resultSet->toArray())->send();
    }

    /**
     * Home action.
     * @return void
     * @throws
     * @Route("/chartlet", methods="POST", name="products")
     */
    public function saveAction()
    {
        $data = $this->request->getPost();
        try{
            $data['items'] = json_encode($data['items']);
            $data['cropImages'] = json_encode($data['cropImages']);
            
            $filter = ['name'];
            $columnMap = ['groupname'=>'name'];
            if(!empty($data['id'])){
                $config = ProductConfigClip::findFirstById($data['id']);
            }else{
                $config = new ProductConfigClip();
            }
            
            $success = $config->save($data);
            if(!$success){
                $msg =core_get_model_message($config);
                $this->resultSet->error(1004,$msg);
            }

            $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
    }

    /**
     * Home action.
     * @return void
     * @throws
     * @Route("/chartlet", methods="DELETE", name="products")
     */
    public function deleteAction()
    {
        $this->app->admin->core->api->Helper()->deleteRecord('Product\Model\ProductConfigClip');
        $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
    }
}

