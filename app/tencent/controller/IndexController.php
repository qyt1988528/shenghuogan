<?php

namespace Tencent\Controller;
use MDK\Controller;


/**
 * tencent controller.
 * @RoutePrefix("/tencent", name="tencent")
 */
class IndexController extends Controller
{
    private $_error;

    public function initialize()
    {
        $config = $this->app->core->config->config->toArray();
        $this->_error = $config['error_message'];
    }
    /**
     * 微信session.
     * @return void
     * @Route("/login", methods="GET", name="tencent")
     */
    public function loginAction()
    {
        $jsCode = $this->request->getParam('js_code',null,'');
        if(empty($jsCode)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
            $wxdata = $this->app->tencent->api->WeChat()->getSessionByCode($jsCode);
            if(empty($wxdata)){
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
            if(isset($wxdata['session_key'])){
                //unset($wxdata['session_key']);
            }
            $data['data'] = $wxdata;
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
     * 创建
     * Create action.
     * @return void
     * @Route("/createuser", methods="POST", name="tencent")
     */
    public function createAction() {
        //权限验证
        $postData = $this->request->getPost();
        $insertFields = $this->app->tencent->api->Helper()->getInsertFields();
        foreach ($insertFields as $v){
            if(empty($postData[$v])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
        }
        try{
            $insert = $this->app->tencent->api->Helper()->createUser($postData);
            if(empty($insert)){
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
            $data =[
                'id' => $insert
            ];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
    public function getSessionKeyAction()
    {
        //获取微信登录sessionKey
        $jsCode = $this->request->getParam('js_code',null,'',true);
        var_dump($jsCode);exit;
        try{
            $data = $this->app->tencent->api->WeChat()->getSession($jsCode);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    	//获取微信登录sessionKey
	public function getSessionKeyAction()
	{
		$this->_required('js_code');
        $jsCode = $this->getQuery('js_code');

        $config = \Yaf\Registry::get("config")->weixin;
        $wechat = new \Weixin\Web\MiniProgram($config['appid'], $config['secret']);
        $result = $wechat->getSessionKey($jsCode);
        if(isset($result['errcode']) && isset($result['errmsg'])){
            throw new \Exception('errcode:' . $result['errcode'] . ';errmsg:'. $result['errmsg'], 4022);
        }
        $this->output($result);
    }

    public function getWXACodeUnlimitAction()
    {
        $this->_required('scene');
        $scene = $this->getPost('scene');
        $page = $this->getPost('page');
        $page = (empty($page) || !$page) ? "pages/productInfo/productInfo" : $page;
        $config = \Yaf\Registry::get("config")->weixin;
        $ufileConfig = \Yaf\Registry::get('config')->ufile;
        $ufileConfig = $ufileConfig->toArray();
        $ufile = new \Http\UFile(new \Yaf\Config\Simple($ufileConfig));

        $media = new \Weixin\Web\Media($config['appid'], $config['secret']);
        $tokenRes = $media->getAccessToken();
        $imageRes = $media->getwxacodeunlimit($tokenRes['access_token'], json_encode(['scene'=>$scene,'page'=>$page]));
        $newFile = "/tmp/" . time().".jpg";
        file_put_contents($newFile, $imageRes);
        $ret = $ufile->putFile("group_wx/".time().".jpg", $newFile);
        $this->output($ufile->getUrl($ret));
    }

     */

}