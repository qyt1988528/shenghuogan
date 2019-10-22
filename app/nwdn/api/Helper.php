<?php
namespace Nwdn\Api;

use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use Nwdn\Model\NwdnCreateFaceTask;
use Nwdn\Model\NwdnCreateTaskLog;
use Psr\Http\Message\ResponseInterface;

class Helper extends Api
{
    private $_client;
    private $_global_client;
    public function __construct() {
        $this->_client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->app->nwdn->config->nwdn->baseUri,
            // You can set any number of default request options.
            'timeout'  => $this->app->nwdn->config->nwdn->timeout,
            //handler
            'handler' => $this->_getStack(),
        ]);
        $this->_global_client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->app->nwdn->config->nwdn->globalUri,
            // You can set any number of default request options.
            'timeout'  => $this->app->nwdn->config->nwdn->timeout,
            //handler
            'handler' => $this->_getStack(),
        ]);
    }

    protected function _getStack()
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push(Middleware::mapResponse($this->_getMapResponse()));
        return $stack;
    }

    /**
     * 响应报错处理
     * @return \Closure
     */
    protected function _getMapResponse()
    {
        return function( $response){
            if($response->getStatusCode() != 200){
                throw new \Exception("NetWork Error :get response code ".$response->getStatusCode(),1);
            }
            $result = $response->getBody()->getContents();
            $result = json_decode($result,true);
            if($result['code'] != 0){
                throw new \Exception($result['message'],2);
            }
            return $response;
        };
    }

    /**
     * api 签名
     * @param $path
     * @param $params
     * @return array
     */
    protected function _getHeader(string $path ,array $params,$global=false)
    {
        if($global){
            $str = $this->app->nwdn->config->nwdn->globalUri.$path.json_encode($params);
        }else{
            $str = $this->app->nwdn->config->nwdn->baseUri.$path.json_encode($params);
        }

        $str = base64_encode($str).$this->app->nwdn->config->nwdn->privatekey;
        return [
            'token' => $this->app->nwdn->config->nwdn->token,
            'md5' => md5($str)
        ];
    }

    /**
     * 获取请求参数
     * @param $params
     * @return array
     */
    protected function _getParams(array $params)
    {
        $data = [
            'timeStamp' => strval(time()),
        ];
        $data = array_merge($data,$params);
        return $data;
    }

    /**
     * 获取tencent ai
     * @return Client
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * 判断图片是否模糊
     * @param $imgLink
     * @param $imgMd5
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTask($imgLink,$imgMd5,$source=''){
        $params = $this->_getParams([
            'imgLink' => $imgLink,
            'imgMd5' => $imgMd5,
        ]);
        $header  = $this->_getHeader('rs2c/createTask',$params);
        $response = $this->_client->request('POST', '/rs2c/createTask',[
            "body" => base64_encode(json_encode($params)),
            "headers" => $header,
        ]);

        $result = $response->getBody();
        $result = json_decode($result,true);
        $data = $result['data'];
        if(in_array($data,[101,103])){
            $data['tip'] = '图片单边像素超过2048或大于20m';
        }
        $data['create'] = true;
        $data['img_link'] = $imgLink;
        $data['img_md5'] = $imgMd5;
        $data['source'] = $source;
        $this->logTask($data);
        return $data;
    }

    /**
     * 创建和查询任务时记录日志
     * @param $result
     */
    public function logTask($result)
    {
        try{
            if(!empty($result['create'])){
                //新增
                if(!empty($result['taskid'])){
                    $data['host'] = $this->request->getHeader('origin');
                    $data['host'] = parse_url($data['host']);
                    $data['host'] = isset($data['host']['host'])?$data['host']['host']:'';
                    $data['path'] = $this->request->getURI();
                    $data['path'] = parse_url($data['path']);
                    $data['path'] = isset($data['path']['path']) ?$data['path']['path'] :'';
                    $insertData = [
                        'image_link' => $result['img_link'] ?? '',
                        'image_md5' => $result['img_md5'] ?? '',
                        'source_name' => $result['source'] ?? '',
                        'task_id' => $result['taskid'],
                        'host' => $data['host'],
                        'path' => $data['path'],
                        'create_task_data' => json_encode($result),
                    ];
//                    $insertData['created_at'] = $insertData['updated_at'] = date('Y-m-d H:i:s');
                    $model = new NwdnCreateTaskLog();
                    $model->save($insertData);
                }else{
                }
            }else{
                //更新
                if(!empty($result['taskid'])){
                    $updateData = [
                        'state' => $result['state'] ?? -2,
                        'update_task_data' => json_encode($result),
                    ];
                    //先查询获取之前任务对应的id
                    $model = new NwdnCreateTaskLog();
                    $taskData = $model->find([
                        'conditions'=>"task_id=:task_id:",
                        'order' => 'id desc',
                        'limit' => 1,
                        'bind'=>[ 'task_id'=>$result['taskid'] ]
                    ])->toArray();
//                    $updateData['updated_at'] = date('Y-m-d H:i:s');
                    if(!empty($taskData)){
                        $taskData = current($taskData);
                        $id = (int)$taskData['id'];
                        $updateModel = NwdnCreateTaskLog::find('id='.$id);
                        $updateData = array_merge(['id'=>$id],$updateData);
                        $updateModel->update($updateData);
                    }else{
                        //历史数据，在创建任务的时候还没有记录日志，在之后查询此任务时，记录其状态
                        $updateData['task_id'] = $result['taskid'];
//                        $updateData['created_at'] = date('Y-m-d H:i:s');
                        $model->create($updateData);
                    }
                }else{
                }
            }
        }catch (\Exception $e){
//            var_dump($e);
        }

    }

    /**
     * 获取任务状态
     * @param $taskId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTask($taskId)
    {
        $params = $this->_getParams([
            'taskid' => $taskId,
            ]);
        $header  = $this->_getHeader('rs2c/getTask',$params);
        $response = $this->_client->request('POST', 'rs2c/getTask',[
            "body" => base64_encode(json_encode($params)),
            "headers" => $header,
//            'debug' => true
        ]);

        $result = $response->getBody();
        $result = json_decode($result,true);
        $data = $result['data'];
        if($data['phase'] == 0){
            $data['state'] = -1;//未开始处理
        }elseif($data['phase'] < 7){
            $data['state'] = 0;//处理中
        }elseif($data['phase'] == 7){
            $data['state'] = 1;//处理成功
        }else{
            $data['state'] = 2;//任务出错
        }
        $data['create'] = false;
        $this->logTask($data);
        return $data;
    }

    public function getUserInfo()
    {
        $params = $this->_getParams([]);
        $header  = $this->_getHeader('rs2c/userInfo',$params);
        $response = $this->_client->request('POST', 'rs2c/userInfo',[
            "body" => base64_encode(json_encode($params)),
            "headers" => $header,
//            'debug' => true
        ]);

        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result['data'];
    }

    /**
     *
     * 查询任务列表
     * @param $page
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTaskList($page)
    {
        $params = $this->_getParams(['page'=>$page]);
        $header  = $this->_getHeader('rs2c/taskList',$params);
        $response = $this->_client->request('POST', 'rs2c/taskList',[
            "body" => base64_encode(json_encode($params)),
            "headers" => $header,
//            'debug' => true
        ]);

        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result['data'];
    }

    /**
     * 创建换脸任务
     * 2019-10-12  http://nwdn.bigwinepot.com/rs2c/ 改成 https://global.bigwinepot.com/nwdn/
     * @param $input 用户头像图片url
     * @param $emid 根据 sku 和 肤色 定义的值
     * @param string $source
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createFaceTask($input,$emid,$source=''){//,$background=''
        $params = $this->_getParams([
            'input_url' => $input,
//            'background_url' => $background,
            'emid' => $emid,
        ]);
        $header  = $this->_getHeader('nwdn/createFaceTask',$params,true);
        $response = $this->_global_client->request('POST', '/nwdn/createFaceTask',[
            "body" => base64_encode(json_encode($params)),
            "headers" => $header,
        ]);

        $result = $response->getBody();
        $result = json_decode($result,true);

        $data['data'] = $result['data'] ?? [];
        $data['create'] = true;
        $data['input_url'] = $input;
        $data['emid'] = $emid;
//        $data['background_url'] = $background;
        $data['source'] = $source;
        if(!empty($data['data'])){
            $this->logFaceTask($data);
        }
        return $data;
    }

    /**
     * 根据任务ID 查询换脸任务数据
     * 2019-10-12  http://nwdn.bigwinepot.com/rs2c/ 改成 https://global.bigwinepot.com/nwdn/
     * @param $taskId
     * @param string $source
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getFaceTask($taskId,$source=''){
        $params = $this->_getParams([
            'taskid' => $taskId,
        ]);
        $header  = $this->_getHeader('nwdn/getFaceTask',$params,true);
        $response = $this->_global_client->request('POST', '/nwdn/getFaceTask',[
            "body" => base64_encode(json_encode($params)),
            "headers" => $header,
        ]);

        $result = $response->getBody();
        $result = json_decode($result,true);
        $data['data'] = $result['data'] ?? [];
        $data['create'] = false;
        $data['source'] = $source;
        $data['taskid'] = $taskId;
        if(!empty($data['data'])){
            $this->logFaceTask($data);
        }
        return $data;
    }
    /**
     * 创建和查询换脸任务时记录日志
     * @param $result
     */
    public function logFaceTask($result)
    {
        try{
            if(!empty($result['create'])){
                //新增
                if(!empty($result['data']['taskid'])){
                    if(isset($result['source']) && !empty($result['source'])){
                        $tmpData = explode('_',$result['source']);
                        $data['host'] = $tmpData[0];
                        $data['path'] = $tmpData[1];
                    }else{
                        $data['host'] = $this->request->getHeader('origin');
                        $data['host'] = parse_url($data['host']);
                        $data['host'] = isset($data['host']['host'])?$data['host']['host']:'';
                        $data['path'] = $this->request->getURI();
                        $data['path'] = parse_url($data['path']);
                        $data['path'] = isset($data['path']['path']) ?$data['path']['path'] :'';
                    }
                    $tmpData = explode('_',$result['emid']);
                    $data['sku'] = $tmpData[0];
                    $data['ethnicity'] = $tmpData[1];
                    $insertData = [
                        'ethnicity' => $data['ethnicity'] ?? '',
                        'sku' => $data['sku'] ?? '',
                        'emid' => $result['emid'] ?? '',
                        'input_url' => $result['input_url'] ?? '',
                        'taskid' => $result['data']['taskid'] ?? '',
                        'phase' => $result['data']['phase'] ?? -1,
                        'host' => $data['host'] ?? '',
                        'path' => $data['path'] ?? '',
                        'create_task_data' => json_encode($result['data']),
                    ];
//                    $insertData['created_at'] = $insertData['updated_at'] = date('Y-m-d H:i:s');
                    $model = new NwdnCreateFaceTask();
                    $model->create($insertData);
                }else{
                }
            }else{
                //更新
                if(!empty($result['data']['taskid'])){
                    $updateData = [
                        'phase' => $result['data']['phase'] ?? -2,
                        'output_url' => $result['data']['output_url'] ?? '',
                        'update_task_data' => json_encode($result['data']),
                    ];
                    //先查询获取之前任务对应的id
                    $model = new NwdnCreateFaceTask();
                    $taskData = $model->find([
                        'conditions'=>"taskid=:taskid:",
                        'order' => 'id desc',
                        'limit' => 1,
                        'bind'=>[ 'taskid'=>$result['data']['taskid'] ]
                    ])->toArray();
//                    $updateData['updated_at'] = date('Y-m-d H:i:s');
                    if(!empty($taskData)){
                        $taskData = current($taskData);
                        $id = (int)$taskData['id'];
                        $updateModel = NwdnCreateFaceTask::find('id='.$id);
                        $updateData = array_merge(['id'=>$id],$updateData);
                        $updateModel->update($updateData);
                    }else{
                        //历史数据，在创建任务的时候还没有记录日志，在之后查询此任务时，记录其状态
                        $updateData['taskid'] = $result['taskid'];
//                        $updateData['created_at'] = date('Y-m-d H:i:s');
                        $model->create($updateData);
                    }
                }else{
                }
            }
        }catch (\Exception $e){
//            var_dump($e);
        }

    }
}