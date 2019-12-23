<?php
namespace Address\Controller;
use MDK\Controller;


/**
 * Index controller.
 * @RoutePrefix("/address", name="address")
 */
class IndexController extends Controller
{
    private $_error;
    private $_level;

    public function initialize()
    {
        $config = $this->app->core->config->config->toArray();
        $this->_error = $config['error_message'];
        $this->_level = $config['region_level'];
    }

    /**
     * 获取省
     * test action.
     * @return void
     * @Route("/test", methods="GET", name="address")
     */
    public function testAction(){
        var_dump(111);exit;

    }
    /**
     * 获取省
     * provinceList action.
     * @return void
     * @Route("/provinceList", methods="GET", name="address")
     */
    public function provinceListAction(){
        try{
            $result = $this->app->address->api->Helper()->getListByPid();
            if(!empty($result)){
                $data['data'] = [
                    'province_list' => $result
                ];
            }else{
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    //获取市
    /**
     * 获取市
     * cityList action.
     * @return void
     * @Route("/cityList", methods="GET", name="address")
     */
    public function cityListAction(){
        $provinceId = (int)$this->request->getParam('id',null,0);
        if(empty($provinceId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
            $provinceData = $this->app->address->api->Helper()->getRegionById($provinceId);
            if(empty($provinceData) || $provinceData['level']==$this->_level['province']){
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
            $result = $this->app->address->api->Helper()->getListByPid($provinceId);
            if(!empty($result)){
                $data['data'] = [
                    'city_list' => $result
                ];
            }else{
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    //获取区
    /**
     * 获取市
     * countyList action.
     * @return void
     * @Route("/countyList", methods="GET", name="address")
     */
    public function countyListAction(){
        $cityId = (int)$this->request->getParam('id',null,0);
        if(empty($cityId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
            $cityData = $this->app->address->api->Helper()->getRegionById($cityId);
            if(empty($cityData) || $cityData['level']==$this->_level['city']){
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
            $result = $this->app->address->api->Helper()->getListByPid($cityId);
            if(!empty($result)){
                $data['data'] = [
                    'county_list' => $result
                ];
            }else{
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

}
