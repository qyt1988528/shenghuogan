<?php
%header%
namespace %nameUpper%\Controller;

use %defaultModuleUpper%\Controller\AbstractController;

/**
 * Index controller.
 *
 * @category Maiyuan\Module
 * @package  Controller
 *
 * @RoutePrefix("/%name%s", name="%name%s")
 */
class IndexController extends AbstractController
{
    /**
     * Module index action.
     *
     * @return void
     *
     * @Route("/", methods={"GET"}, name="%name%s")
     */
    public function indexAction()
    {

    }
}
