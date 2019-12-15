<?php

namespace Rent\Controller;

use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/showcar", name="showcar")
 */
class ShowCarController extends Controller
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
     * @Route("/", methods="GET", name="showcar")
     */
    public function indexAction()
    {
        $page = $this->request->getParam('page', null, 1);
        //分页
        try {
            $data['data'] = [];
            $tickets = $this->app->rent->api->Car()->getList($page);
            if (!empty($tickets)) {
                $data['data'] = $tickets;
            }
        } catch (\Exception $e) {
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }

    /**
     * mergeFace action.
     * 商品详情
     * @return void
     * @Route("/detail", methods="GET", name="showcar")
     */
    public function detailAction()
    {
        $goodsId = $this->request->getParam('id', null, '');
        if (empty($goodsId)) {
            $this->resultSet->error(1001, $this->_error['invalid_input']);
        }
        try {
            $result = $this->app->rent->api->Car()->detail($goodsId);
            if (empty($result)) {
                $this->resultSet->error(1002, $this->_error['not_exist']);
            }
            $data['data'] = $result;
        } catch (\Exception $e) {
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }


}
