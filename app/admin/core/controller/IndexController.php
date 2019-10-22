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
 * @RoutePrefix("/admin", name="pushs")
 */
class IndexController extends Controller
{
    /**
     * admin home action.
     * @return void
     * @Route("/", methods="GET", name="home")
     */
    public function indexAction()
    {
        echo 112131231231;
    }

    /**
     * admin home action.
     * @return void
     * @Route("/", methods="POST", name="home")
     */
    public function postAction()
    {
        echo 112131231231;
    }
}

