<?php

namespace Admin\Core\Controller;

use Phalcon\Mvc\Controller;

use Phalcon\Paginator\Adapter\QueryBuilder as PaginatorQueryBuilder;

/**
 * Index controller.
 * @require Module checkout,admin
 * @category Maiyuan\Module
 * @package  Controller
 *
 * @RoutePrefix("/admin/qiniu", name="qiniu")
 */
class QiniuController extends Controller
{
    /**
     * admin home action.
     * @return void
     * @Route("/token", methods="GET", name="qiniu")
     */
    public function gettoeknAction()
    {
        $data = $this->app->admin->core->api->Qiniu()->getToken();
        $this->response->setJsonContent($this->resultSet->success()->setData($data)->toArray())->send();
    }

    /**
     * admin home action.
     * @return void
     * @Route("/", methods="POST", name="qiniu")
     */
    public function postAction()
    {
        echo 112131231231;
    }
}