<?php

namespace Admin\User\Controller;

use Phalcon\Mvc\Controller;
use Admin\User\Model\AdminUser;
use Phalcon\Paginator\Adapter\QueryBuilder as PaginatorQueryBuilder;

/**
 * Index controller.
 * @require Module checkout,admin
 * @category Maiyuan\Module
 * @package  Controller
 *
 * @RoutePrefix("/admin", name="pushs")
 */
class IndexController extends Controller
{
    /**
     * admin home action.
     * @return void
     * @Route("/accounts", methods="GET", name="admin_user")
     */
    public function indexAction()
    {
        $where = $limit = $orderPhql = $type = $keyword  = '';
        $prop = 'id';
        $sort = 'DESC';
        $builder = $this->modelsManager->createBuilder()
            ->columns(" a.username as account,a.id,a.cnname,a.enname,a.last_login_time as create_time,a.avatar ")
            ->from(["a"=>"Admin\User\Model\AdminUser"]);


        if($this->request->has('keyword') && $this->request->has('type')){
            $keyword = $this->request->get('keyword');
            $type = $this->request->get('type');
            $keyword = '%'.$keyword.'%';
            $builder->where('  a.'.trim($type).' like "'.trim($keyword).'" ');
        }
        if($this->request->has('order') && $this->request->has('prop')){
            $prop = $this->request->get('prop');
            if($this->request->get('order') == 'ascending'){
                $sort = 'ASC';
            }else{
                $sort = 'DESC';
            }
            $builder->orderBy($prop.' '.$sort);
        }
        $paginator = new PaginatorQueryBuilder(
            [
                "builder" => $builder,
                "limit"   => $this->request->get('limit',null,15),
                "page"    => $this->request->get('page',null,1),
            ]
        );
        $data['list'] = $paginator->getPaginate()->items->toArray();
        $data['order'] = ['prop'=>$prop,'desc'=>$sort];
        $data['total'] = $paginator->getPaginate()->total_items;
        $this->resultSet->set('data',$data)->success();
        $this->response->setJsonContent($this->resultSet->toArray())->send();
    }

    /**
     * admin home action.
     * @return void
     * @Route("/accounts/get", methods="GET", name="admin_user")
     */
    public function detailAction()
    {
        $id = $this->request->get('id');
        $phql = 'SELECT au.id,au.username as account,au.avatar,au.cnname,au.enname,au.active,group_concat(aru.role_id) as groupid from Admin\User\Model\AdminUser as au 
				JOIN Admin\User\Model\AdminRoleUser as aru ON au.id=aru.user_id WHERE au.id=:id: GROUP BY au.id';
        $admin = $this->modelsManager->executeQuery(
            $phql,
            [
                "id"    => $id,
            ]
        );

        if($admin){
            $admin = $admin->toArray()[0];
            $admin['active'] = $admin['active']*1;
            $admin['groupid'] = explode(',', $admin['groupid']);
            $data['data'] = $admin;
            $phql = 'SELECT ar.id,ar.name as groupname FROM Admin\User\Model\AdminRole as ar ';
            $group = $this->modelsManager->executeQuery($phql);
            $data['group'] = $group->toArray();
            $this->resultSet->set('data',$data)->success();

        }else{
            $this->resultSet->error(1005,'no record');
        }
        $this->response->setJsonContent($this->resultSet->toArray())->send();
    }

    /**
     * save account detail action.
     * @return void
     * @throws
     * @Route("/accounts/save", methods="POST", name="admin_user")
     */
    public function saveAction()
    {
        $data = $this->request->getPost();
        if(empty($data['avatar'])){
            unset($data['avatar']);
        }
        $filter = ['avatar','id','active','username','enname','cnname','password'];
        $admin = AdminUser::findFirstById($data['id']);
        $success = $admin->save(
            $data,
            $filter
        );
        $data['groupid'] = explode(',',$data['groupid']);
        if(!empty($data['groupid']) && is_array($data['groupid'])){
            $phql = 'DELETE FROM Admin\User\Model\AdminRoleUser  WHERE user_id='.$data['id'];
            $this->modelsManager->executeQuery($phql);
            $this->app->admin->user->api->Helper()->setRole($admin,$data['groupid']);
        }
        if(!$success){
            $msg =$this->getErrorMessage($admin);
            $this->resultSet->error(1004,$msg);
        }
        $this->response->setJsonContent($this->resultSet->success()->toArray())->send();
    }

    /**
     * save account detail action.
     * @return void
     * @throws
     * @Route("/accounts/add", methods="POST", name="admin_user")
     */
    public function addaccountsAction()
    {
        $data = $this->request->getPost();
        $data['password'] = md5($data['password']);
        $filter = ['avatar','active','username','enname','cnname','password'];
        if(!AdminUser::count("username='".$data['username']."'")){
            $admin = new AdminUser();
            $success = $admin->save(
                $data,
                $filter
            );
            if($success){
                $data['groupid'] = explode(',',$data['groupid']);
                if(!empty($data['groupid']) && is_array($data['groupid'])){
                    $this->app->admin->user->api->Helper()->setRole($admin,$data['groupid']);
                }
                $this->resultSet->success();
            }else{
                $msg =$this->getErrorMessage($admin);
                $this->resultSet->error(1004,$msg);
            }
        }else{
            $this->resultSet->error(2002,'account already exists');
        }
        $this->response->setJsonContent($this->resultSet->toArray())->send();

    }
}

