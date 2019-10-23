<?php
namespace Core\Command;


use MDK\Console\AbstractCommand;
use MDK\Console\CommandInterface;
use MDK\Console\ConsoleUtil;
use MDK\Exception;
use PhpAmqpLib\Exchange\AMQPExchangeType;

/**
 * Test command.
 *
 * @CommandName(['deal_task'])
 * @CommandDescription('deal_task management.')
 */
class ConsumerTaskRmq extends AbstractCommand implements CommandInterface{
    public $accessLog = 'consumer_task_access';
    public $errorLog = 'consumer_task_error';
    public $normalErrorLog = 'consumer_task_normal_error';
    public $accessFunc = 'log';
    public $errorFunc = 'error';
    public $consumerTag = 'consumer_task_ai_image';

    public $consumerExchangeInfo = [
        'exchange' => 'nwdn_task_exchange',
        'queue'=> 'nwdn_task_queue',
        'route_key' => 'route_task_wxsyb',
    ];
    public $repeatCount = 50;

    public function errorMessage(){
        $errorInfo = error_get_last();
        if(!empty($errorInfo)){
            $errorMessage = "error_type:{$errorInfo['type']},error_message:{$errorInfo['message']},error_file:{$errorInfo['file']},error_line:{$errorInfo['line']}!";
            $this->app->core->api->Log()->writeLog('',$errorMessage,$this->normalErrorLog,$this->errorFunc);
        }
    }
    public function syncAction(){
        while(true){
            try{
                //pro
                $consumerConnInfo = $this->app->core->config->rabbitmq->product->toArray();
                //test
//                $consumerConnInfo = $this->app->core->config->rabbitmq->test->toArray();
                $callback = function ($message){
                    try{
                        $erpRequestInfo = $this->app->core->config->erp->toArray();
                        if(!empty($message->body)){
                            $msg = $message->body;
                            $datas = json_decode($msg, true);
                            $canAck = true;
                            if(empty($datas['init_data']['image_key']) || empty($datas['init_data']['image_url']) || empty($datas['create_task_ret']['taskid']) ){
                                $this->app->core->api->Log()->writeLog($datas,'Have no need deal data(image_key or image_url or taskid)',$this->errorLog,$this->errorFunc);
                                $canAck = false;
                            }else{
                                $this->app->core->api->Log()->writeLog($datas,'start consumer task-data',$this->accessLog,$this->accessFunc);
                                $imageUrl = $datas['init_data']['image_url'];
                                $imageKey = $datas['init_data']['image_key'];
                                $taskId = $datas['create_task_ret']['taskid'];
                                $erpType = $datas['init_data']['erp_type'] ?? 'test';
                                //查询任务状态
                                $this->app->core->api->Log()->writeLog($taskId,'get task start with',$this->accessLog,$this->accessFunc);
                                $getTaskRet = $this->app->nwdn->api->Helper()->getTask($taskId);
                                $this->app->core->api->Log()->writeLog($getTaskRet,'get task end with',$this->accessLog,$this->accessFunc);
                                if(!empty($getTaskRet)){
                                    if(isset($getTaskRet['state'])){
                                        if( $getTaskRet['state']==1 || $getTaskRet['state']==2 ){
                                            //1处理成功-传处理后的图,2任务出错-传原图
                                            if($getTaskRet['state']==1){
                                                if(!empty($getTaskRet['output_url'])){
                                                    $imageUrl = $getTaskRet['output_url'];
                                                }else{
                                                    $this->app->core->api->Log()->writeLog($getTaskRet,'task reulst state is 1 ,but have no output_url',$this->errorLog,$this->errorFunc);
                                                    $canAck = false;
                                                }
                                            }else{
                                                $this->app->core->api->Log()->writeLog($taskId,'task result state is 2 nwdn_task_failed',$this->errorLog,$this->errorFunc);
                                            }
                                            //通知ERP
                                            //pro
                                            $postData = $erpRequestInfo[$erpType];
                                            //test
//                                    $postData = $erpRequestInfo['test'];
                                            $postData['params'] = json_encode([$imageKey=>$imageUrl,'clarity_type' => 1]);
                                            $this->app->core->api->Log()->writeLog($postData,'notice erp start with(image dealed by nwdn)',$this->accessLog,$this->accessFunc);
                                            $erpRet = $this->app->core->api->Erp()->notice($postData);
                                            $this->app->core->api->Log()->writeLog($erpRet,'notice erp end with(image dealed by nwdn)',$this->accessLog,$this->accessFunc);
                                        }else{
                                            // -1未开始处理,0处理中
                                            //放回队列
                                            //test
                                            $publishConnInfo = $this->app->core->config->rabbitmq->test->toArray();
                                            //pro
                                            $publishConnInfo = $this->app->core->config->rabbitmq->product->toArray();
                                            $this->app->core->api->Log()->writeLog($datas,' publish rmq start with (task is processing)',$this->accessLog,$this->accessFunc);
                                            if(empty($datas['repeat'])){
                                                $datas['repeat'] = 1;
                                            }else{
                                                $datas['repeat']++;
                                            }
                                            if($datas['repeat'] > $this->repeatCount){
                                                //通知ERP
                                                //pro
                                                $postData = $erpRequestInfo[$erpType];
                                                //test
//                                    $postData = $erpRequestInfo['test'];
                                                $postData['params'] = json_encode([$imageKey=>$imageUrl,'clarity_type' => 0]);
                                                $this->app->core->api->Log()->writeLog($postData,'notice erp start with(image dealed by nwdn)',$this->accessLog,$this->accessFunc);
                                                $erpRet = $this->app->core->api->Erp()->notice($postData);
                                                $this->app->core->api->Log()->writeLog($erpRet,'notice erp end with(image dealed by nwdn)',$this->accessLog,$this->accessFunc);
                                            }else{
                                                $publishData = json_encode($datas);
                                                $this->app->core->api->RabbitmqPublisher()->publish($publishConnInfo,$this->consumerExchangeInfo,$publishData);
                                                $this->app->core->api->Log()->writeLog($taskId,' publish rmq end (task is process)',$this->accessLog,$this->accessFunc);
                                            }
                                        }
                                    }else{
                                        //获取任务状态出错
                                        $message = $getTaskRet['message'] ?? '';
                                        $this->app->core->api->Log()->writeLog($message,'task result have no state ',$this->errorLog,$this->errorFunc);
                                        $canAck = false;
                                    }
                                }else{
                                    //获取任务失败
                                    $this->app->core->api->Log()->writeLog('','task result is empty',$this->errorLog,$this->errorFunc);
                                    $canAck = false;
                                }
                                if($canAck){
                                    $this->app->core->api->Log()->writeLog('consumer task end',' basic_task_ack  ',$this->accessLog,$this->accessFunc);
                                    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                                }
                            }
                        }else{
                            //消息为空
                            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
//                    $this->app->core->api->Log()->writeLog('','Can connect to rabbitmq!But no data can be consumered',$this->errorLog,$this->errorFunc);
                            sleep(60);//挂起1分钟
                        }
                        $this->errorMessage();
                    }catch (\Error $e){
                        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                        $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                        $this->app->core->api->Log()->writeLog($errorMessage,'consumer have error',$this->errorLog,$this->errorFunc);
                    }catch (\Exception $e){
//                var_dump($e->getMessage());
                        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                        $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                        $this->app->core->api->Log()->writeLog($errorMessage,'consumer have exception',$this->errorLog,$this->errorFunc);
                    }
                };


                $this->app->core->api->RabbitmqConsumer()->consumer($consumerConnInfo,$this->consumerExchangeInfo,$callback,$this->consumerTag,AMQPExchangeType::DIRECT);
//                $this->app->core->api->RabbitmqConsumer()->consumer($consumerConnInfo,$this->consumerExchangeInfo,$this->_consumerTaskData(),$this->consumerTag,AMQPExchangeType::DIRECT);
                $this->errorMessage();
            }catch (\Error $e){
                $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                $this->app->core->api->Log()->writeLog($errorMessage,'Have error',$this->errorLog,$this->errorFunc);
            }catch (\Exception $e){
//            var_dump($e->getMessage());
                $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                $this->app->core->api->Log()->writeLog($errorMessage,'Have exception',$this->errorLog,$this->errorFunc);
            }
        }
    }

    /**
     * 获取图片超分结果
     * 处理成功或失败，通知ERP
     * 未处理或处理中，放回队列
     * @return \Closure
     */
    private function _consumerTaskData(){
        $callback = function ($message){
            try{
                $erpRequestInfo = $this->app->core->config->erp->toArray();
                if(!empty($message->body)){
                    $msg = $message->body;
                    $datas = json_decode($msg, true);
                    $canAck = true;
                    if(empty($datas['init_data']['image_key']) || empty($datas['init_data']['image_url']) || empty($datas['create_task_ret']['taskid']) ){
                        $this->app->core->api->Log()->writeLog($datas,'Have no need deal data(image_key or image_url or taskid)',$this->errorLog,$this->errorFunc);
                        $canAck = false;
                    }else{
                        $this->app->core->api->Log()->writeLog($datas,'start consumer task-data',$this->accessLog,$this->accessFunc);
                        $imageUrl = $datas['init_data']['image_url'];
                        $imageKey = $datas['init_data']['image_key'];
                        $taskId = $datas['create_task_ret']['taskid'];
                        $erpType = $datas['init_data']['erp_type'] ?? 'test';
                        //查询任务状态
                        $this->app->core->api->Log()->writeLog($taskId,'get task start with',$this->accessLog,$this->accessFunc);
                        $getTaskRet = $this->app->nwdn->api->Helper()->getTask($taskId);
                        $this->app->core->api->Log()->writeLog($getTaskRet,'get task end with',$this->accessLog,$this->accessFunc);
                        if(!empty($getTaskRet)){
                            if(isset($getTaskRet['state'])){
                                if( $getTaskRet['state']==1 || $getTaskRet['state']==2 ){
                                    //1处理成功-传处理后的图,2任务出错-传原图
                                    if($getTaskRet['state']==1){
                                        if(!empty($getTaskRet['output_url'])){
                                            $imageUrl = $getTaskRet['output_url'];
                                        }else{
                                            $this->app->core->api->Log()->writeLog($getTaskRet,'task reulst state is 1 ,but have no output_url',$this->errorLog,$this->errorFunc);
                                            $canAck = false;
                                        }
                                    }else{
                                        $this->app->core->api->Log()->writeLog($taskId,'task result state is 2 nwdn_task_failed',$this->errorLog,$this->errorFunc);
                                    }
                                    //通知ERP
                                    //pro
                                    $postData = $erpRequestInfo[$erpType];
                                    //test
//                                    $postData = $erpRequestInfo['test'];
                                    $postData['params'] = json_encode([$imageKey=>$imageUrl,'clarity_type' => 1]);
                                    $this->app->core->api->Log()->writeLog($postData,'notice erp start with(image dealed by nwdn)',$this->accessLog,$this->accessFunc);
                                    $erpRet = $this->app->core->api->Erp()->notice($postData);
                                    $this->app->core->api->Log()->writeLog($erpRet,'notice erp end with(image dealed by nwdn)',$this->accessLog,$this->accessFunc);
                                }else{
                                    // -1未开始处理,0处理中
                                    //放回队列
                                    //test
                                    $publishConnInfo = $this->app->core->config->rabbitmq->test->toArray();
                                    //pro
                                    $publishConnInfo = $this->app->core->config->rabbitmq->product->toArray();
                                    $this->app->core->api->Log()->writeLog($datas,' publish rmq start with (task is processing)',$this->accessLog,$this->accessFunc);
                                    if(empty($datas['repeat'])){
                                        $datas['repeat'] = 1;
                                    }else{
                                        $datas['repeat']++;
                                    }
                                    if($datas['repeat'] > $this->repeatCount){
                                        //通知ERP
                                        //pro
                                        $postData = $erpRequestInfo[$erpType];
                                        //test
//                                    $postData = $erpRequestInfo['test'];
                                        $postData['params'] = json_encode([$imageKey=>$imageUrl,'clarity_type' => 0]);
                                        $this->app->core->api->Log()->writeLog($postData,'notice erp start with(image dealed by nwdn)',$this->accessLog,$this->accessFunc);
                                        $erpRet = $this->app->core->api->Erp()->notice($postData);
                                        $this->app->core->api->Log()->writeLog($erpRet,'notice erp end with(image dealed by nwdn)',$this->accessLog,$this->accessFunc);
                                    }else{
                                        $publishData = json_encode($datas);
                                        $this->app->core->api->RabbitmqPublisher()->publish($publishConnInfo,$this->consumerExchangeInfo,$publishData);
                                        $this->app->core->api->Log()->writeLog($taskId,' publish rmq end (task is process)',$this->accessLog,$this->accessFunc);
                                    }
                                }
                            }else{
                                //获取任务状态出错
                                $message = $getTaskRet['message'] ?? '';
                                $this->app->core->api->Log()->writeLog($message,'task result have no state ',$this->errorLog,$this->errorFunc);
                                $canAck = false;
                            }
                        }else{
                            //获取任务失败
                            $this->app->core->api->Log()->writeLog('','task result is empty',$this->errorLog,$this->errorFunc);
                            $canAck = false;
                        }
                        if($canAck){
                            $this->app->core->api->Log()->writeLog('consumer task end',' basic_task_ack  ',$this->accessLog,$this->accessFunc);
                            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                        }
                    }
                }else{
                    //消息为空
//                    $this->app->core->api->Log()->writeLog('','Can connect to rabbitmq!But no data can be consumered',$this->errorLog,$this->errorFunc);
                    sleep(60);//挂起1分钟
                }
                $this->errorMessage();
            }catch (\Error $e){
                $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                $this->app->core->api->Log()->writeLog($errorMessage,'consumer have error',$this->errorLog,$this->errorFunc);
            }catch (\Exception $e){
//                var_dump($e->getMessage());
                $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                $this->app->core->api->Log()->writeLog($errorMessage,'consumer have exception',$this->errorLog,$this->errorFunc);
            }
        };
        return $callback;
    }

}

