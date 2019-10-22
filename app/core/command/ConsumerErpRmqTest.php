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
 * @CommandName(['deal_erp_image_test'])
 * @CommandDescription('Test management.')
 */
class ConsumerErpRmqTest extends AbstractCommand implements CommandInterface{
    protected $imageMaxSize = 1048576;//1048576bytes = 1MB
    public $accessLog = 'consumer_erp_access_test';
    public $errorLog = 'consumer_erp_error_test';
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
    public $erpProVhost = [
        'merp_original',
        'merp_zhuhai',
        'serp'
    ];
    public $localRmq = [
        'host' => '127.0.0.1',
        'port' => '5672',
        'user' => 'guest',
        'password' => 'guest',
        'vhost'=>'/'
    ];
    /**
     * 消费ERP队列中的数据
     */
    public function syncAction(){
        while(true){
            try{
                $consumerConnInfo = $this->app->core->config->rabbitmq->erp->toArray();
                $consumerConnInfo = $this->localRmq;
                $this->app->core->api->RabbitmqConsumer()->consumer($consumerConnInfo,$this->consumerExchangeInfo,$this->_consumerErpImageData(),$this->consumerTag,AMQPExchangeType::TOPIC);
            }catch (\Exception $e){
//            var_dump($e->getMessage());
                $this->app->core->api->Log()->writeLog($e->getMessage(),'Have exception',$this->errorLog,$this->errorFunc);
            }
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
                foreach($message as $k=>$v){
                    var_dump($k);
                }
                var_dump($message->body_size);
                $erpRequestInfo = $this->app->core->config->erp->toArray();
                if(!empty($message->body)){
                    $msg = $message->body;
                    $datas = json_decode($msg, true);
                    $queueData = $datas['data']['queue_data'] ?? [];
                    if(empty($queueData)){
                        $this->app->core->api->Log()->writeLog($datas,'Have no need deal data(key with queue_data)',$this->errorLog,$this->errorFunc);
                        $canAck = false;
                    }else{
                        $canAck = true;
                        $queueDatas = json_decode($queueData,true);
                        $this->app->core->api->Log()->writeLog($queueDatas,'start consumer erp-data',$this->accessLog,$this->accessFunc);
                        if(empty($queueDatas['type']) || empty($erpRequestInfo[$queueDatas['type']])){
                            $this->app->core->api->Log()->writeLog('','have no erp-type or type is error',$this->errorLog,$this->errorFunc);
                            $canAck = false;
                        }else{
                            $erpType = $queueDatas['type'];
                            //queue_data->key表示唯一标识,queue_data->url表示图片原地址
                            foreach ($queueDatas as $v){
                                if(!empty($v['photo_ai']) && !empty($v['unique_code'])){
                                    $imageKey = $v['unique_code'];
                                    $imageUrl = $v['photo_ai'];
                                    $this->app->core->api->Log()->writeLog($v,'single deal imageKey&imageUrl ',$this->accessLog,$this->accessFunc);
                                    //暂不判断ERP 图片地址 减少网络请求
                                    /*
                                    $headers = @get_headers($imageUrl);
                                    if(!empty($headers) && isset($headers[0]) && $headers[0]=="HTTP/1.1 200 OK"){
                                    }else{
                                        //图片地址有误
                                        $this->app->core->api->Log()->writeLog($v,'erp-imgUrl is not exist',$this->errorLog,$this->errorFunc);
                                        $canAck = false;
                                    }
                                    */
                                    $limitSize = $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
                                    $imageUrlData = parse_url($imageUrl);
                                    if(!empty($imageUrlData['host']) && $imageUrlData['host'] =='spic.qn.cdn.imaiyuan.com'){
                                        //七牛图片自带图片缩放功能
                                        $imageOriginalInfo = file_get_contents($imageUrl.'?imageInfo');
                                        if(!empty($imageOriginalInfo) ){
                                            $imageOriginalInfo = json_decode($imageOriginalInfo,true);
                                            $this->app->core->api->Log()->writeLog($imageOriginalInfo,' qiniu image info',$this->accessLog,$this->accessFunc);
                                            if(!empty($imageOriginalInfo['size']) && !empty($imageOriginalInfo['width']) && !empty($imageOriginalInfo['height'])){
                                                if($imageOriginalInfo['size']<$limitSize){
                                                    //原图小于1MB，满足腾讯模糊识别接口
                                                    $imageBase64str = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
                                                }else{
                                                    //原图大于1MB，不满足腾讯模糊识别接口，需要进行压缩
                                                    $imageSizeLimitUrl = $imageUrl.'?imageMogr2/size-limit/1000k';//腾讯模糊识别限制图片大小为1MB
                                                    $imageBase64str = $this->app->core->api->Image()->getBase64ByImageUrl($imageSizeLimitUrl);
                                                }
                                                if(!empty($imageBase64str)){
                                                    //调API，判断图片是否模糊，传图片base64str
                                                    $this->app->core->api->Log()->writeLog('','judge image is or not fuzzy by tencent start',$this->accessLog,$this->accessFunc);//base64数据 记录日志会导致。。。
                                                    $fuzzyResult = $this->app->tencent->api->Helper()->isFuzzy($imageBase64str);
                                                    $this->app->core->api->Log()->writeLog($fuzzyResult,'judge image is or not fuzzy by tencent end with',$this->accessLog,$this->accessFunc);
                                                    if(!empty($fuzzyResult) && isset($fuzzyResult['fuzzy'])){
                                                        //test
                                                        $fuzzyResult['fuzzy'] = true;
                                                        if($fuzzyResult['fuzzy']){
                                                            //模糊，创建图片增强任务，得到任务ID，此时作为生产者，产生队列
                                                            $size = 20*$sizeTrillion;//20MB
                                                            if($imageOriginalInfo['size']<$size && $imageOriginalInfo['height']<2048 && $imageOriginalInfo['width']<2048){
                                                                //注：如原图片单边像素不超过2048且小于20MB，传原图 图片地址，图片md5
                                                                $imgLink = $imageUrl;
                                                            }else{
                                                                //注：如原图片单边像素超过2048 或 大于20MB，使用七牛自带压缩原图 图片地址，图片md5
                                                                //获取压缩比例
                                                                $rate = $this->app->core->api->Image()->getCompressRate($imageOriginalInfo['height'],$imageOriginalInfo['width'],2000,2000,2000*2000);
                                                                $lastWidth = intval($imageOriginalInfo['width']*$rate);
                                                                $imgLink = $imageUrl.'?imageMogr2/auto-orient/thumbnail/'.$lastWidth.'x/format/jpg/blur/1x0/quality/100|imageslim';
                                                            }
                                                            $headers = @get_headers($imgLink);
                                                            if(!empty($headers) && isset($headers[0]) && $headers[0]=="HTTP/1.1 200 OK"){
                                                                $imgMd5 = md5_file($imgLink);
                                                                if(empty($imgMd5) || !is_string($imgMd5)){//有次会得到true/false 此时请求nwdn-api将消耗1元大洋
                                                                    $this->app->core->api->Log()->writeLog([$imgLink,$imgMd5],'image_md5 is error',$this->errorLog,$this->errorFunc);
                                                                    $canAck = false;
                                                                }else{
                                                                    $this->app->core->api->Log()->writeLog([$imgLink,$imgMd5],'create task by nwdn start with',$this->accessLog,$this->accessFunc);
                                                                    //test
                                                                    $createTaskRet = [
                                                                        'taskid' => 'b1746dbb752f8c08db7c27290739c744',
                                                                    ];
                                                                    sleep(3);//暂停3秒,代替创建超分任务
                                                                    //pro
//                                                    $createTaskRet = $this->app->nwdn->api->Helper()->createTask($imgLink,$imgMd5 ,'consumer_erp_rmq');
                                                                    $this->app->core->api->Log()->writeLog($createTaskRet,'create task by nwdn end with',$this->accessLog,$this->accessFunc);
                                                                    if(empty($createTaskRet['taskid'])){
                                                                        //创建任务失败
                                                                        $this->app->core->api->Log()->writeLog($createTaskRet,'$this->app->nwdn->api->Helper()->createTask('.$imgLink.','.$imgMd5.' ) result have no taskid',$this->errorLog,$this->errorFunc);
                                                                        $canAck = false;
                                                                    }else{
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
//                                                        $publishConnInfo = $this->app->core->config->rabbitmq->product->toArray();
                                                                        $this->app->core->api->Log()->writeLog($publishData,' publish rmq start with (image is fuzzy,have created nwdn task)',$this->accessLog,$this->accessFunc);
                                                                        $publishData = json_encode($publishData);
                                                                        $this->app->core->api->RabbitmqPublisher()->publish($publishConnInfo,$this->publishExchangeInfo,$publishData);
                                                                        $this->app->core->api->Log()->writeLog('',' publish rmq end (image is fuzzy,haved created nwdn task)',$this->accessLog,$this->accessFunc);
                                                                    }
                                                                }
                                                            }else{
                                                                //图片地址有误
                                                                $this->app->core->api->Log()->writeLog($imgLink,'qiniu imageurl  get_headers error ,can not create task',$this->errorLog,$this->errorFunc);
                                                                $canAck = false;
                                                            }
                                                        }else{
                                                            //不模糊，通知ERP
                                                            //pro
                                                            $postData = $erpRequestInfo[$queueDatas['type']];
                                                            //test
                                                            $postData = $erpRequestInfo['test'];
                                                            $postData['params'] = json_encode([$imageKey=>$imageUrl,'clarity_type' => 0]);
                                                            $this->app->core->api->Log()->writeLog($postData,'notice erp start with(image is not fuzzy)',$this->accessLog,$this->accessFunc);
                                                            $erpRet = $this->app->core->api->Erp()->notice($postData);
                                                            $this->app->core->api->Log()->writeLog($erpRet,'notice erp end with(image is not fuzzy)',$this->accessLog,$this->accessFunc);
                                                        }
                                                    }else{
                                                        //获取是否模糊数据有误
                                                        $this->app->core->api->Log()->writeLog($fuzzyResult,'$this->app->tencent->api->Helper()->isFuzzy() result is error by qiniu image base64str',$this->errorLog,$this->errorFunc);
                                                        $canAck = false;
                                                    }
                                                }else{
                                                    //获取七牛图片base64str失败
                                                    $this->app->core->api->Log()->writeLog('','get qiniu image64str is error',$this->errorLog,$this->errorFunc);
                                                    $canAck = false;
                                                }
                                            }else{
                                                //获取七牛图片所需信息失败
                                                $this->app->core->api->Log()->writeLog($imageUrl.'?imageInfo','get qiniu imageInfo success but info is error',$this->errorLog,$this->errorFunc);
                                                $canAck = false;
                                            }
                                        }else{
                                            //获取七牛图片信息失败
                                            $this->app->core->api->Log()->writeLog($imageUrl.'?imageInfo','get qiniu imageInfo failed',$this->errorLog,$this->errorFunc);
                                            $canAck = false;
                                        }
                                    }else{
                                        //其他图片
                                        //图片压缩，传图片二进制
//                                $limitSize = $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
                                        $this->app->core->api->Log()->writeLog([$imageUrl,$limitSize],'compress image start with',$this->accessLog,$this->accessFunc);
                                        $imageBlob = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
                                        if(empty($imageBlob)){
                                            $this->app->core->api->Log()->writeLog('','file_get_content image failed',$this->errorLog,$this->errorFunc);
                                            $canAck = false;
                                        }else{
                                            $imageCompressRet = $this->app->core->api->Image()->compressImage($imageBlob,$limitSize);
                                            $this->app->core->api->Log()->writeLog('','compress image end with',$this->accessLog,$this->accessFunc);//$imageCompressRet中base64和二进制数据 记录日志会导致。。。
                                            //判断模糊条件 图片上限1MB
                                            if(empty($imageCompressRet['image_base64str'])){
                                                $this->app->core->api->Log()->writeLog('','image compress result base64 is empty',$this->errorLog,$this->errorFunc);
                                                $canAck = false;
                                            }else{
                                                //调API，判断图片是否模糊，传图片base64str
                                                $this->app->core->api->Log()->writeLog('','judge image is or not fuzzy by tencent start',$this->accessLog,$this->accessFunc);//base64数据 记录日志会导致。。。
                                                $fuzzyResult = $this->app->tencent->api->Helper()->isFuzzy($imageCompressRet['image_base64str']);
                                                $this->app->core->api->Log()->writeLog($fuzzyResult,'judge image is or not fuzzy by tencent end with',$this->accessLog,$this->accessFunc);
                                                if(!empty($fuzzyResult) && isset($fuzzyResult['fuzzy'])){
                                                    //test
                                                    $fuzzyResult['fuzzy'] = true;
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
                                                            $canAck = false;
                                                        }else{
                                                            $headers = @get_headers($imgLink);
                                                            if(!empty($headers) && isset($headers[0]) && $headers[0]=="HTTP/1.1 200 OK"){
                                                                $imgMd5 = md5_file($imgLink);
                                                                if(!empty($imgMd5) && !is_string($imgMd5)){//有次会得到true/false 此时请求nwdn-api将消耗1元大洋
                                                                    $this->app->core->api->Log()->writeLog([$imgLink,$imgMd5],'image_md5 is error',$this->errorLog,$this->errorFunc);
                                                                    $canAck = false;
                                                                }else{
                                                                    $this->app->core->api->Log()->writeLog([$imgLink,$imgMd5],'create task by nwdn start with',$this->accessLog,$this->accessFunc);
                                                                    //test
                                                                    $createTaskRet = [
                                                                        'taskid' => 'b1746dbb752f8c08db7c27290739c744',
                                                                    ];
                                                                    sleep(3);//暂停3秒,代替创建超分任务
                                                                    //pro
//                                        $createTaskRet = $this->app->nwdn->api->Helper()->createTask($imgLink,$imgMd5 ,'consumer_erp_rmq');
                                                                    $this->app->core->api->Log()->writeLog($createTaskRet,'create task by nwdn end with',$this->accessLog,$this->accessFunc);
                                                                    if(empty($createTaskRet['taskid'])){
                                                                        //创建任务失败
                                                                        $this->app->core->api->Log()->writeLog($createTaskRet,'$this->app->nwdn->api->Helper()->createTask('.$imgLink.','.$imgMd5.' ) result have no taskid',$this->errorLog,$this->errorFunc);
                                                                        $canAck = false;
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
//                                            $publishConnInfo = $this->app->core->config->rabbitmq->product->toArray();
                                                                        $this->app->core->api->Log()->writeLog($publishData,' publish rmq start with (image is fuzzy,have created nwdn task)',$this->accessLog,$this->accessFunc);
                                                                        $publishData = json_encode($publishData);
                                                                        $this->app->core->api->RabbitmqPublisher()->publish($publishConnInfo,$this->publishExchangeInfo,$publishData);
                                                                        $this->app->core->api->Log()->writeLog('',' publish rmq end (image is fuzzy,haved created nwdn task)',$this->accessLog,$this->accessFunc);
                                                                    }
                                                                }
                                                            }else{
                                                                //图片地址有误
                                                                $this->app->core->api->Log()->writeLog($imgLink,'image is fuzzy,but after deal imgLink is not exist',$this->errorLog,$this->errorFunc);
                                                                $canAck = false;
                                                            }
                                                        }
                                                    }else{
                                                        //不模糊，通知ERP
                                                        //pro
                                                        $postData = $erpRequestInfo[$queueDatas['type']];
                                                        //test
                                                        $postData = $erpRequestInfo['test'];
                                                        $postData['params'] = json_encode([$imageKey=>$imageUrl,'clarity_type' => 0]);
                                                        $this->app->core->api->Log()->writeLog($postData,'notice erp start with(image is not fuzzy)',$this->accessLog,$this->accessFunc);
                                                        $erpRet = $this->app->core->api->Erp()->notice($postData);
                                                        $this->app->core->api->Log()->writeLog($erpRet,'notice erp end with(image is not fuzzy)',$this->accessLog,$this->accessFunc);
                                                    }
                                                }else{
                                                    //获取是否模糊数据有误
                                                    $this->app->core->api->Log()->writeLog($fuzzyResult,'$this->app->tencent->api->Helper()->isFuzzy() result is error by compress_result',$this->errorLog,$this->errorFunc);
                                                    $canAck = false;
//                                $this->app->core->api->Log()->writeLog($fuzzyResult,'$this->app->tencent->api->Helper()->isFuzzy('.$imageCompressRet['image_base64str'].') result is error',$this->errorLog,$this->errorFunc);
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    //非图片数据，不处理
                                }
                            }
                            //数据无误，就应答
                            if($canAck){
                                $this->app->core->api->Log()->writeLog('consumer end',' basic_ack  ',$this->accessLog,$this->accessFunc);
                                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                            }
                        }
                    }
                }else{
                    //消息为空,暂无图片需要处理
                    sleep(60);//挂起1分钟
//                    $this->app->core->api->Log()->writeLog('','Can connect to rabbitmq!But no data can be consumered',$this->errorLog,$this->errorFunc);
                }

            }catch (\Exception $e){
//                var_dump($e->getMessage());
                $this->app->core->api->Log()->writeLog($e->getMessage(),'consumer have exception',$this->errorLog,$this->errorFunc);
            }
        };
        return $callback;
    }

    private function _consumerSomeVhost(){
        $callback = function ($message){
            try{
                if(!empty($message->body)){
                    $msg = $message->body;
                    $datas = json_decode($msg, true);
                    var_dump($datas);
                }
            }catch (\Exception $e){
            }
        };
        return $callback;

    }
}
