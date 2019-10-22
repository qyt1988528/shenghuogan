<?php

namespace Admin\User\Controller;

use MDK\Controller;
use Phalcon\Mvc\Model\Query;
use Phalcon\Paginator\Adapter\QueryBuilder as PaginatorQueryBuilder;
use Admin\User\Model\AdminRole;
use Admin\User\Model\AdminRoleRouter;
use Admin\User\Model\AdminRoleUser;
use Admin\User\Model\AdminUser;
use Admin\User\Model\AdminRouter;
/**
 * Home controller.
 * @RoutePrefix("/admin/routers", name="home")
 */
class RouterController extends Controller
{
    private $apiRouter;
    public function onConstruct()
    {
        $this->apiRouter = $this->app->admin->user->api->Router();
    }

    /**
     * get router action.
     * @return void
     * @throws
     * @Route("/", methods="GET", name="home")
     */
    public function indexAction()
    {
        $this->resultSet->set('data',$this->apiRouter->getData())->success();
        $this->response->setJsonContent($this->resultSet->toArray())->send();
    }
    /**
     * save router action.
     * @return void
     * @throws
     * @Route("/", methods="POST", name="home")
     */
    public function saveAction()
    {
        $filter = [
            'id',
            'parent_id',
            'name',
            'path',
            'view',
            'icon',
            'hidden',
            'url',
            'redirect',
            'sort',
        ];
        $data = $this->request->getPost();

        if(empty($data['parent_id'])  && isset($data['tree'])){
            $data['parent_id'] = end($data['tree']);
        }elseif(empty($data['parent_id']) && empty($data['true'])){
            $data['parent_id'] = 0;
        }
        $router = new AdminRouter();
        $success = $router->save(
            $data,
            $filter
        );
        if(!$success){
            $msg =$this->app->admin->core->api->Helper()->getModelMessage($router);
            $this->resultSet->error(1004,$msg);
        }
        $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
    }
    /**
     * delete router action.
     * @return void
     * @throws
     * @Route("/", methods="DELETE", name="home")
     */
    public function deleteAction()
    {
        $this->app->admin->core->api->Helper()->deleteRecord('Admin\User\Model\AdminRouter');
        $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
    }
    /**
     * delete router action.
     * @return void
     * @throws
     * @Route("/hidden", methods="PUT", name="home")
     */
    public function hiddenAction()
    {
        $id = $this->request->getPut('id');
        $hidden = $this->request->getPut('hidden')=='true'?'false':'true';
        $query = $this->modelsManager->createQuery("UPDATE  Admin\User\Model\AdminRouter SET hidden=:hidden: WHERE id = :id:");
        $result = $query->execute(
            [
                "id" => $id,
                "hidden" => $hidden,
            ]
        );
        if($result->success() === false){
            $this->resultSet->error(1003,'update failed')->send();
        }
        $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
    }

}