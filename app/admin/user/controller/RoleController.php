<?php

namespace Admin\User\Controller;

use MDK\Controller;
use Phalcon\Mvc\Model\Query;
use Phalcon\Paginator\Adapter\QueryBuilder as PaginatorQueryBuilder;
use Admin\User\Model\AdminRole;
use Admin\User\Model\AdminRoleRouter;
use Admin\User\Model\AdminRoleUser;
use Admin\User\Model\AdminUser;
/**
 * Home controller.
 * @RoutePrefix("/admin/group", name="home")
 */
class RoleController extends Controller
{

    public function onConstruct()
    {
        $this->apiRouter = $this->app->admin->user->api->Router();
    }

    /**
     * Home action.
     * @return void
     * @throws
     * @Route("/", methods="GET", name="home")
     */
    public function indexAction()
    {
        $builder = $this->modelsManager->createBuilder()
            ->from(['ar'=>"Admin\User\Model\AdminRole"])
            ->columns('ar.id , ar.name as groupname ,group_concat(arr.router_id) as privilege')
            ->join('Admin\User\Model\AdminRoleRouter','arr.role_id = ar.id','arr','LEFT')
            ->groupBy(['ar.id']);

        if($this->request->has('keyword')){
            $keyword = $this->request->get('keyword');
            $builder->where('ar.name like :keyword:',["keyword"=>"%".$keyword."%"]);
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
        $data['list']['router'] = $this->apiRouter->getGroupRouter();
        $this->resultSet->set('data',$data)->success();
        $this->response->setJsonContent($this->resultSet->toArray())->send();
    }
    /**
     * delete role action.
     * @return void
     * @throws
     * @Route("/", methods="DELETE", name="home")
     */
    public function deleteAction()
    {
        $this->app->admin->core->api->Helper()->deleteRecord('Admin\User\Model\AdminRole');
        $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
    }

    /**
     * delete role action.
     * @return void
     * @throws
     * @Route("/add", methods="POST", name="home")
     */
    public function addAction()
    {
        $data = $this->request->getPost();

        try{
            if(empty($data['privilege'])){throw new \Exception('请选择菜单',4002);}
            $roleRouter = $data['privilege'];
            $filter = ['name'];
            $columnMap = ['groupname'=>'name'];
            if(!empty($data['id'])){
                $role = AdminRole::findFirstById($data['id']);
            }else{
                $role = new AdminRole();
            }
            $role->assign($data,$columnMap,$filter);
            $success = $role->save();
            if(!$success){
                $msg =core_get_model_message($role);
                $this->resultSet->error(1004,$msg);
            }
            $this->app->admin->user->api->Helper()->power($role,$roleRouter);

            $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
    }

}