<?php
namespace Core\Command;


use MDK\Console\AbstractCommand;
use MDK\Console\CommandInterface;
use MDK\Console\ConsoleUtil;
use MDK\Exception;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use Nwdn\Model\NwdnCreateTaskLog;

/**
 * Test command.
 *
 * @CommandName(['deal_erp_image'])
 * @CommandDescription('Test management.')
 */
class ConsumerErpRmq extends AbstractCommand implements CommandInterface{
    protected $imageMaxSize = 1048576;//1048576bytes = 1MB
    public $accessLog = 'consumer_erp_access';
    public $errorLog = 'consumer_erp_error';
    public $accessFunc = 'log';
    public $errorFunc = 'error';
    public $consumerTag = 'consumer_erp_ai_image';

    public $consumerExchangeInfo = [
        'exchange' => 'ERP_TASK_AIIMAGEDOWNLOAD_EXCHANGE',
        'queue'=>'ERP_TASK_AIIMAGEDOWNLOAD_QUEUE',
        'route_key' => 'route_wxsyb',
    ];
    public $publishExchangeInfo = [
        'exchange' => 'nwdn_task_exchange',
        'queue'=> 'nwdn_task_queue',
        'route_key' => 'route_task_wxsyb',
    ];

    /**
     * 消费ERP队列中的数据
     */
    public function syncAction(){
        try{
            while(true){
                $consumerConnInfo = $this->app->core->config->rabbitmq->erp->toArray();
                $consumerResult = $this->app->core->api->RabbitmqConsumer()->consumer($consumerConnInfo,$this->consumerExchangeInfo,$this->_consumerErpImageData(),$this->consumerTag,AMQPExchangeType::TOPIC);
                sleep(120);
            }
        }catch (\Exception $e){
//            var_dump($e->getMessage());
            $this->app->core->api->Log()->writeLog($e->getMessage(),'Have exception',$this->errorLog,$this->errorFunc);
            return ;
        }

    }

    /**
     * 消费ERP队列中的数据
     * 处理图片流程:
     * 1)压缩图片;
     * 2)调腾讯API判断其是否模糊;
     * 3)不模糊，通知ERP。end
     * 4)模糊，调你我当年API对其创建超分任务;
     * 5)创建任务前需确认原图是否满足其要求，不满足将1）压缩的图片上传至七牛
     * 6）因任务处理需要时间，不能即时返回超分结果，将任务数据放入队列，由另一个脚本进行查询并处理
     * @return \Closure
     */
    private function _consumerErpImageData(){
        $callback = function ($message){
            try{
                $erpRequestInfo = $this->app->core->config->erp->toArray();
                if(!empty($message->body)){
                    $msg = $message->body;
                    $datas = json_decode($msg, true);
                    $queueData = $datas['data']['queue_data'] ?? [];
                    if(empty($queueData)){
                        $this->app->core->api->Log()->writeLog($datas,'Have no need deal data(key with queue_data)',$this->errorLog,$this->errorFunc);
                        return ;
                    }
                    $queueDatas = json_decode($queueData,true);
                    $this->app->core->api->Log()->writeLog($queueDatas,'start consumer erp-data',$this->accessLog,$this->accessFunc);
                    if(empty($queueDatas['type']) || empty($erpRequestInfo[$queueDatas['type']])){
                        $this->app->core->api->Log()->writeLog('','have no erp-type or type is error',$this->errorLog,$this->errorFunc);
                        return ;
                    }
                    $erpType = $queueDatas['type'];
                    //queue_data->key表示唯一标识,queue_data->url表示图片原地址
                    foreach ($queueDatas as $v){
                        if(!empty($v['photo_ai']) && !empty($v['unique_code'])){
                            $imageKey = $v['unique_code'];
                            $imageUrl = $v['photo_ai'];
                            $this->app->core->api->Log()->writeLog($v,'single deal imageKey&imageUrl ',$this->accessLog,$this->accessFunc);
                            $headers = @get_headers($imageUrl);
                            if(!empty($headers) && isset($headers[0]) && $headers[0]=="HTTP/1.1 200 OK"){
                            }else{
                                //图片地址有误
                                $this->app->core->api->Log()->writeLog($v,'erp-imgUrl is not exist',$this->errorLog,$this->errorFunc);
                                return;
                            }
                            //图片压缩，传图片二进制
                            $limitSize = $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
                            $this->app->core->api->Log()->writeLog([$imageUrl,$limitSize],'compress image start with',$this->accessLog,$this->accessFunc);
                            $imageBlob = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
                            $imageCompressRet = $this->app->core->api->Image()->compressImage($imageBlob,$limitSize);
                            $this->app->core->api->Log()->writeLog('','compress image end with',$this->accessLog,$this->accessFunc);//$imageCompressRet中base64和二进制数据 记录日志会导致。。。
                            //判断模糊条件 图片上限1MB
                            if(empty($imageCompressRet['image_base64str'])){
                                $this->app->core->api->Log()->writeLog('','image compress result base64 is empty',$this->errorLog,$this->errorFunc);
                                return;
                            }
                            //调API，判断图片是否模糊，传图片base64str
                            $this->app->core->api->Log()->writeLog('','judge image is or not fuzzy by tencent start',$this->accessLog,$this->accessFunc);//base64数据 记录日志会导致。。。
                            $fuzzyResult = $this->app->tencent->api->Helper()->isFuzzy($imageCompressRet['image_base64str']);
                            $this->app->core->api->Log()->writeLog($fuzzyResult,'judge image is or not fuzzy by tencent end with',$this->accessLog,$this->accessFunc);
                            if(!empty($fuzzyResult) && isset($fuzzyResult['fuzzy'])){
                                if($fuzzyResult['fuzzy']){
                                    //模糊，创建图片增强任务，得到任务ID，此时作为生产者，产生队列
                                    $size = 20*$sizeTrillion;//20MB
                                    if($imageCompressRet['image_size_original']<$size && $imageCompressRet['image_height_original']<2048 && $imageCompressRet['image_width_original']<2048){
                                        //注：如原图片单边像素不超过2048且小于20MB，传原图 图片地址，图片md5
                                        $imgLink = $imageUrl;
                                    }else{
                                        //注：如原图片单边像素超过2048 或 大于20MB，传压缩后图 图片地址，图片md5
                                        //传七牛获取图片地址
                                        $blob = $imageCompressRet['image_blob'] ?? '';
                                        $pathParts = pathinfo($imageUrl);
                                        $imageOriginalName = $pathParts['filename'];//获取图片名称
//                                    $imageOriginalName = $imageCompressRet['image_name_original'] ?? '';
                                        $imageName = 'erp/compress/'.date('Ymd').'/'.$imageOriginalName.'.jpg';
                                        $this->app->core->api->Log()->writeLog('','upload compress image to qiniu start',$this->accessLog,$this->accessFunc);
                                        $qiniuUploadRet = $this->app->admin->core->api->Qiniu()->uploadBlobToQiniu($blob,$imageName);
                                        $this->app->core->api->Log()->writeLog($qiniuUploadRet,'upload compress image to qiniu end with',$this->accessLog,$this->accessFunc);
                                        if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
                                            $imgLink = $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url'];
                                        }else{
                                            $imgLink = '';
                                        }
                                    }
                                    if(empty($imgLink)){
                                        $this->app->core->api->Log()->writeLog('','image is fuzzy,but after deal imgLink is empty',$this->errorLog,$this->errorFunc);
                                        return;
                                    }
                                    $headers = @get_headers($imgLink);
                                    if(!empty($headers) && isset($headers[0]) && $headers[0]=="HTTP/1.1 200 OK"){
                                    }else{
                                        //图片地址有误
                                        $this->app->core->api->Log()->writeLog($imgLink,'image is fuzzy,but after deal imgLink is not exist',$this->errorLog,$this->errorFunc);
                                        return;
                                    }
//                                    $imgMd5 = md5($imgLink);
                                    $imgMd5 = md5_file($imgLink);
                                    if(!is_string($imgMd5)){//有次会得到true/false 此时请求nwdn-api将消耗1元大洋
                                        $this->app->core->api->Log()->writeLog([$imgLink,$imgMd5],'image_md5 is error',$this->errorLog,$this->errorFunc);
                                        return;
                                    }
                                    $this->app->core->api->Log()->writeLog([$imgLink,$imgMd5],'create task by nwdn start with',$this->accessLog,$this->accessFunc);
                                    //test
                                    $createTaskRet = [
                                        'taskid' => 'b1746dbb752f8c08db7c27290739c744',
                                    ];
                                    //pro
                                    $createTaskRet = $this->app->nwdn->api->Helper()->createTask($imgLink,$imgMd5 ,'consumer_erp_rmq');
                                    $this->app->core->api->Log()->writeLog($createTaskRet,'create task by nwdn end with',$this->accessLog,$this->accessFunc);
                                    if(empty($createTaskRet['taskid'])){
                                        //创建任务失败
                                        $this->app->core->api->Log()->writeLog($createTaskRet,'$this->app->nwdn->api->Helper()->createTask('.$imgLink.','.$imgMd5.' ) result have no taskid',$this->errorLog,$this->errorFunc);
                                        return ;
                                    }else{
//                                    $taskId = $createTaskRet['taskid'];
                                        //保存到队列 以便下一步处理
                                        $initData = [
                                            'image_key' => $imageKey,
                                            'image_url' => $imageUrl,
                                            'item_id' => $v['item_id'] ?? 0,
                                            'erp_type' => $erpType,
                                        ];
                                        $publishData = [
                                            'init_data' => $initData,
                                            'create_task_ret' => $createTaskRet,
                                        ];
                                        //test
                                        $publishConnInfo = $this->app->core->config->rabbitmq->test->toArray();
                                        //pro
                                        $publishConnInfo = $this->app->core->config->rabbitmq->product->toArray();
                                        $this->app->core->api->Log()->writeLog($publishData,' publish rmq start with (image is fuzzy,have created nwdn task)',$this->accessLog,$this->accessFunc);
                                        $publishData = json_encode($publishData);
                                        $this->app->core->api->RabbitmqPublisher()->publish($publishConnInfo,$this->publishExchangeInfo,$publishData);
                                        $this->app->core->api->Log()->writeLog('',' publish rmq end (image is fuzzy,haved created nwdn task)',$this->accessLog,$this->accessFunc);
                                    }
                                }else{
                                    //不模糊，通知ERP
                                    //pro
                                    $postData = $erpRequestInfo[$queueDatas['type']];
                                    //test
//                                    $postData = $erpRequestInfo['test'];
                                    $postData['params'] = json_encode([$imageKey=>$imageUrl,'clarity_type' => 0]);
                                    $this->app->core->api->Log()->writeLog($postData,'notice erp start with(image is not fuzzy)',$this->accessLog,$this->accessFunc);
                                    $erpRet = $this->app->core->api->Erp()->notice($postData);
                                    $this->app->core->api->Log()->writeLog($erpRet,'notice erp end with(image is not fuzzy)',$this->accessLog,$this->accessFunc);
                                }
                            }else{
                                //获取是否模糊数据有误
                                $this->app->core->api->Log()->writeLog($fuzzyResult,'$this->app->tencent->api->Helper()->isFuzzy('.$imageCompressRet['image_base64str'].') result is error',$this->errorLog,$this->errorFunc);
                                return ;
                            }
                        }else{

                        }
                    }
                    $this->app->core->api->Log()->writeLog('consumer end',' basic_ack  ',$this->accessLog,$this->accessFunc);
                    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                    sleep(300);//挂起5分钟
                }else{
                    //消息为空,暂无图片需要处理
                    sleep(300);//挂起5分钟
//                    $this->app->core->api->Log()->writeLog('','Can connect to rabbitmq!But no data can be consumered',$this->errorLog,$this->errorFunc);
                    return ;
                }

            }catch (\Exception $e){
//                var_dump($e->getMessage());
                $this->app->core->api->Log()->writeLog($e->getMessage(),'Have exception',$this->errorLog,$this->errorFunc);
                return;
            }
        };
        return $callback;
    }
}
