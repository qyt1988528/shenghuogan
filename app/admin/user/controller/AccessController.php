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
 * @RoutePrefix("/admin", name="admin")
 */
class AccessController extends Controller
{
    /**
     * admin home action.
     * @return void
     * @Route("/access", methods="POST", name="admin_user")
     */
    public function indexAction()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $user = AdminUser::findFirst(array(
            'conditions'=>'username="'.$username.'" and password="'.md5($password).'" and active = 1'
        ));
        if(!$user){
            $this->resultSet->error(2001,'username or password invalid');
        }
        $data = $user->toArray();
        unset($data['password']);
        if(!$data['avatar']){
            $data['avatar'] = "http://orgvvlmv9.bkt.clouddn.com/FqbMINlCxOya97_DABqyaEw1Wy_L";
        }
        $data['token'] = $this->validator->getToken($user);
        $this->resultSet->set('data',$data);
        //组权限
        $phql = 'SELECT aru.role_id FROM Admin\User\Model\AdminRoleUser as aru WHERE aru.user_id = "' . $user->id . '"';
        $query = $this->modelsManager->createQuery($phql);
        $roles = $query->execute();
        if (!$roles) {
            $this->resultSet->error(4001,'no authorization');
        }
        $roleids = '';

        foreach ($roles as $key => $value) {
            if ($key == 0) {
                $roleids .= $value->role_id;
            } else {
                $roleids .= ',' . $value->role_id;
            }
        }
        //router
        $sql = 'SELECT ar.* FROM Admin\User\Model\AdminRouter AS ar LEFT JOIN Admin\User\Model\AdminRoleRouter as arr ON ar.id=arr.router_id 
        WHERE arr.role_id IN ('.$roleids.') or ar.view="" ORDER BY ar.sort ';
        $router = $this->modelsManager->executeQuery($sql);
        $routes = $this->app->admin->user->api->Helper()->formatRouter($router->toArray());
        $this->resultSet->set('router',$routes)->success();

        $this->response->setJsonContent($this->resultSet->toArray())->send();
    }
}

