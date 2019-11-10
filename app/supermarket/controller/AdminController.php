<?php
namespace Supermarket\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/supermarketadmin", name="supermarketadmin")
 */
class AdminController extends Controller
{

    /**
     * Index action.
     * @return void
     * @Route("/", methods="GET", name="supermarketadmin")
     */
    public function createAction() {
        $title = '';
        $data =[];
        try{
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }

    public function deleteAction() {

    }
    public function withdrawAction() {

    }
    public function updateAction() {

    }

}
