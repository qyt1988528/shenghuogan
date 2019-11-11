<?php
namespace Supermarket\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/supermarketadmin", name="supermarketadmin")
 */
class AdminController extends Controller
{
    public $insertFields = [
        'title',
        'img_url',
        'original_price',
        'self_price',
        'description',
        'specs',
    ];

    /**
     * Index action.
     * @return void
     * @Route("/", methods="GET", name="supermarketadmin")
     */
    public function createAction() {
        $postData = $this->request->getPost();
        foreach ($this->insertFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,'Invalid input!');
            }
        }
        try{
            $data =[

            ];
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
