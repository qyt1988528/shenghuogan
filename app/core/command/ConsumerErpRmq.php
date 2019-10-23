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
 * @CommandDescription('deal_erp_image management.')
 */
class ConsumerErpRmq extends AbstractCommand implements CommandInterface{
    protected $imageMaxSize = 1048576;//1048576bytes = 1MB
    public $accessLog = 'consumer_erp_access';
    public $accessErpLog = 'consumer_erp_notice_access';
    public $errorLog = 'consumer_erp_error';
    public $normalErrorLog = 'consumer_erp_normal_error';
    public $accessFunc = 'log';
    public $errorFunc = 'error';
    public $consumerTag = 'consumer_erp_ai_image';
    public $qiniuHost = [
        'spic.qn.cdn.imaiyuan.com',
    ];
    public $matchQiniuHost = [
        '120.76.221.184' => 'mulan.myuxc.com',
        'admin.soufeel.com' => 'soufeel.mulan.myuxc.com',
        'pic.stylelab.com' => 'spic.qn.cdn.imaiyuan.com'
    ];

    public $replaceHost = [
        '120.76.221.184' => '120.76.221.184/public/images',
        'admin.soufeel.com' => 'admin.soufeel.com/share',
        'pic.stylelab.com' => 'pic.stylelab.com/share'
    ];
    public $otherSupportHost = [
        '120.76.221.184',
        'admin.soufeel.com',
        'pic.stylelab.com'
    ];
    public $qiniuThumbnailKey = '-soufeel_super_image_ai';
    public $qiniuThumbnailInfoKey = '-soufeel_super_image_ai_info';
    public $fuzzySource = 'erp_consumer';
    public $nwdnLimit = 2048;

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
    public $repeatCount = 5;
    public $envTest = false;//true;//true--测试,false-表示线上

    /**
     * 记录最后一次错误信息 错误类型、信息、文件、行数
     */
    public function errorMessage(){
        $errorInfo = error_get_last();
        if(!empty($errorInfo)){
            $errorMessage = "error_type:{$errorInfo['type']},error_message:{$errorInfo['message']},error_file:{$errorInfo['file']},error_line:{$errorInfo['line']}!";
            $this->app->core->api->Log()->writeLog('',$errorMessage,$this->normalErrorLog,$this->errorFunc);
        }
    }
    /**
     * 消费ERP队列中的数据
     */
    public function syncAction(){
        while(true){
            try{
                if($this->envTest){
                    //test
                    $consumerConnInfo = $this->app->core->config->rabbitmq->local->toArray();
                }else{
                    //pro
                    $consumerConnInfo = $this->app->core->config->rabbitmq->erp->toArray();
                }
                //获取回调方法,执行消费
                $callback = $this->getCallback();
                $this->app->core->api->RabbitmqConsumer()->consumer($consumerConnInfo,$this->consumerExchangeInfo,$callback,$this->consumerTag,AMQPExchangeType::TOPIC);
                //记录最近的一次报错信息
                $this->errorMessage();
            }catch (\Exception $e){
                $this->writeErrorLog($e,'outside exception');
            }catch (\Error $e){
                $this->writeErrorLog($e,'outside error');
            }
        }

    }

    /**
     * 消费者回调函数
     * @return \Closure
     */
    private function getCallback(){
        $callback = function ($message){
            try{
                $erpRequestInfo = $this->app->core->config->erp->toArray();
                if(!empty($message->body)){
                    $msg = trim($message->body);
                    //记录消费的数据，若MERP后台中存在“已发送”的数据，则可以将 日志中的$msg数据 重新放回队列
                    $this->app->core->api->Log()->writeLog($msg,'start consumer erp-all-data',$this->accessLog,$this->accessFunc);
                    $datas = json_decode($msg, true);
                    $queueData = $datas['data']['queue_data'] ?? [];
                    $canAck = true;//true表示可以应答，false表示存在异常
                    if(empty($queueData)){
                        $this->app->core->api->Log()->writeLog($datas,'Have no need deal data(key with queue_data)',$this->errorLog,$this->errorFunc);
                    }else{
                        $queueDatas = json_decode($queueData,true);
                        $this->app->core->api->Log()->writeLog($queueDatas,'start consumer erp-data',$this->accessLog,$this->accessFunc);
                        if(empty($queueDatas['type']) || empty($erpRequestInfo[$queueDatas['type']])){
                            $this->app->core->api->Log()->writeLog('','have no erp-type or type is error',$this->errorLog,$this->errorFunc);
                        }else{
                            $erpType = $queueDatas['type'];
                            //queue_data->key表示唯一标识,queue_data->url表示图片原地址
                            foreach ($queueDatas as $v){
                                $imageKey = $v['unique_code'] ?? '';
                                $imageUrl = $v['photo_ai'] ?? '';
                                //处理单条数据，目前队列的每条消息中仅为一条数据，若后续改为多条，还需验证此流程是否准确(※)
                                $canAck = $this->consumerSingleData($erpRequestInfo,$queueDatas,$v,$erpType);
                            }
                        }
                    }
                    //数据无误，就应答
                    if($canAck){
                        $this->app->core->api->Log()->writeLog('consumer end',' basic_ack  ',$this->accessLog,$this->accessFunc);
                        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                    }else{
                        //数据出错，重新放回队列末尾
                        $this->republishData($datas);
                        //正常应答
                        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                    }
                }else{
                    //消息为空,暂无图片需要处理
                    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                }
                $this->errorMessage();
            }catch (\Exception $e){
                //为保证流程闭环、防止程序卡死，当出现异常时，亦需要通知ERP，并应答队列。
                $this->noticeErp($imageKey,$imageUrl,$erpRequestInfo,$queueDatas,'notice erp (catch exception)');
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                $this->writeErrorLog($e,'inside exception');
            }catch (\Error $e){
                //为保证流程闭环、防止程序卡死，当出现异常时，亦需要通知ERP，并应答队列。
                $this->noticeErp($imageKey,$imageUrl,$erpRequestInfo,$queueDatas,'notice erp (catch error)');
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                $this->writeErrorLog($e,'inside error');
            }
        };
        return $callback;
    }
    /**
     * 通知ERP
     * @param $imageKey
     * @param $imageUrl
     * @param $erpRequestInfo
     * @param $queueDatas
     * @param $message
     */
    private function noticeErp($imageKey,$imageUrl,$erpRequestInfo,$queueDatas,$message){
        if($this->envTest){
            //test
            $postData = $erpRequestInfo['test'];
            $erpRet = '';
        }else{
            //pro
            $postData = $erpRequestInfo[$queueDatas['type']];
        }
        if(!empty($imageUrl) && !empty($imageKey)){
            $postData['params'] = json_encode([$imageKey=>$imageUrl,'clarity_type' => 0]);
            $this->app->core->api->Log()->writeLog($postData,$message.' start',$this->accessErpLog,$this->accessFunc);
            try{
                $erpRet = $this->app->core->api->Erp()->notice($postData);
                $this->app->core->api->Log()->writeLog($erpRet,$message.' end',$this->accessErpLog,$this->accessFunc);
            }catch (\Exception $e){
                $this->writeErrorLog($e,'notice erp have error');
            }
        }else{
            $this->app->core->api->Log()->writeLog('','notice_erp params is error',$this->errorLog,$this->errorFunc);
        }
    }

    /**
     * 记exception/error日志
     * @param $e
     * @param $message
     */
    private function writeErrorLog($e,$message){
        $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
        $this->app->core->api->Log()->writeLog($errorMessage,$message,$this->errorLog,$this->errorFunc);
    }

    /**
     * 重新将数据放回队列
     * 超分时md5_file失败、创建任务失败、
     * 调facepp-api前获取图片base64出错、调facepp-api出错、
     * 大图获取二进制流、压缩大图获取二进制流
     * @param $datas
     */
    private function republishData($datas){
        if(empty($datas['repeat'])){
            $datas['repeat'] = 1;
        }else{
            if($datas['repeat'] > $this->repeatCount){
                $this->app->core->api->Log()->writeLog($datas,' too many repeat ',$this->errorLog,$this->errorFunc);
            }
            $datas['repeat']++;
        }
        $publishData = json_encode($datas);
        $this->app->core->api->Log()->writeLog($publishData,' reject_ack  consumer end ,will restart',$this->accessLog,$this->accessFunc);

        if($this->envTest){
            $publishConnInfo = $this->app->core->config->rabbitmq->local->toArray();
        }else{
            $publishConnInfo = $this->app->core->config->rabbitmq->erp->toArray();
        }
        $this->app->core->api->RabbitmqPublisher()->publish($publishConnInfo,$this->consumerExchangeInfo,$publishData,[],0,AMQPExchangeType::TOPIC);
    }

    /**
     * 创建超分任务后，将数据放入task队列
     * @param $imageKey
     * @param $imageUrl
     * @param $v
     * @param $erpType
     * @param $createTaskRet
     */
    private function publishTaskData($imageKey,$imageUrl,$v,$erpType,$createTaskRet){
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
        if($this->envTest){
            //test
            $publishConnInfo = $this->app->core->config->rabbitmq->local->toArray();
        }else{
            //pro
            $publishConnInfo = $this->app->core->config->rabbitmq->product->toArray();
        }
        $this->app->core->api->Log()->writeLog($publishData,' publish rmq start with (image is blur,have created nwdn task)',$this->accessLog,$this->accessFunc);
        $publishData = json_encode($publishData);
        $this->app->core->api->RabbitmqPublisher()->publish($publishConnInfo,$this->publishExchangeInfo,$publishData);
        $this->app->core->api->Log()->writeLog('',' publish rmq end (image is blur,haved created nwdn task)',$this->accessLog,$this->accessFunc);
    }


    /**
     * 创建超分任务
     * @param $imageKey
     * @param $imageUrl
     * @param $v
     * @param $erpType
     * @param $imgLink
     * @return bool
     */
    private function createTask($imageKey,$imageUrl,$v,$erpType,$imgLink){
        $canAck = true;
        $imgMd5 = md5_file($imgLink);
        if(empty($imgMd5) || !is_string($imgMd5)){//有次会得到true/false 此时请求nwdn-api将消耗1元大洋
            $this->app->core->api->Log()->writeLog([$imgLink,$imgMd5],'image_md5 is error',$this->errorLog,$this->errorFunc);
            $canAck = false;
        }else{
            $this->app->core->api->Log()->writeLog([$imgLink,$imgMd5],'create task by nwdn start with',$this->accessLog,$this->accessFunc);
            if($this->envTest){
                //test
                $tmpTask = '{"taskid":"55a42c38ae38a5cc5eb98aec2848a35f","phase":0,"input_url":"http:\/\/soufeel.mulan.myuxc.com\/custom_product_photos\/original\/20190905\/20190905023106R0TR9v-SCQB13-1.png-soufeel_super_image_ai","source":"consumer_erp_rmq","create_time":"2019-09-05 11:20:08","queue_wait_num":0,"balance":9631,"create":true,"img_link":"http:\/\/soufeel.mulan.myuxc.com\/custom_product_photos\/original\/20190905\/20190905023106R0TR9v-SCQB13-1.png-soufeel_super_image_ai","img_md5":"34c5fb769c05ead0ea1099537711d143"}';
                $createTaskRet = json_decode($tmpTask,true);
                $this->app->nwdn->api->Helper()->logTask($createTaskRet);
                sleep(3);//暂停3秒,代替创建超分任务
            }else{
                //pro
                //有存数据库 操作
                $createTaskRet = $this->app->nwdn->api->Helper()->createTask($imgLink,$imgMd5 ,'consumer_erp_rmq');
            }
            $this->app->core->api->Log()->writeLog($createTaskRet,'create task by nwdn end with',$this->accessLog,$this->accessFunc);
            if(empty($createTaskRet['taskid'])){
                //创建任务失败
                $this->app->core->api->Log()->writeLog($createTaskRet,'createTask with '.$imgLink.','.$imgMd5.' ) failed result have no taskid',$this->errorLog,$this->errorFunc);
                $canAck = false;
            }else{
                //保存到队列 以便下一步处理
                //由另一个脚本消费该数据，对超分任务进行查询，超分结束后通知ERP
                $this->publishTaskData($imageKey,$imageUrl,$v,$erpType,$createTaskRet);
            }
        }
        return $canAck;
    }


    /**
     * 模糊识别处理
     * 通过图片url获取其base64str
     * 调facepp-detect-api进行模糊识别
     * 模糊 -- nwdn-api创建超分任务
     * 不模糊 -- 通知ERP
     * @param $imgLink
     * @param $imageKey
     * @param $imageUrl
     * @param $erpRequestInfo
     * @param $queueDatas
     * @param $v
     * @param $erpType
     * @return bool  false--数据错误,需要重新放回队列处理 true--正确
     */
    private function fuzzyRecognition($imgLink,$imageKey,$imageUrl,$erpRequestInfo,$queueDatas,$v,$erpType){
        $canAck = true;
        $base64str = $this->app->core->api->Image()->getBase64ByImageUrl($imgLink);
        if(!empty($base64str)){
            $faceImageParams = [
//            'image_url' => $imgLink,//因facepp-api访问图片地址时会出现超时
                'image_base64' => $base64str,
            ];

            $this->app->core->api->Log()->writeLog($imgLink,'get detect by face++ start with',$this->accessLog,$this->accessFunc);
            //facepp 人脸识别 为防止数据连接报错 尽量不存数据库 第3个参数为true时表示存数据库 此处暂定为false
            $faceResult = $this->app->face->api->Helper()->faceDetect($faceImageParams,'consumer_erp',false);
            $this->app->core->api->Log()->writeLog($faceResult,'get detect by face++ end with',$this->accessLog,$this->accessFunc);
            if(empty($faceResult) || isset($faceResult['error_message'])){
                //请求face++ api 有误 原样返回
                $canAck = false;
//                $this->noticeErp($imageKey,$imageUrl,$erpRequestInfo,$queueDatas,'notice erp (facepp result is error)');
            }else{
                //人像是否模糊 true--模糊
                $canCreateTask = $this->app->face->api->Helper()->isBlur($faceResult);
                if($canCreateTask){
                    //尺寸满足超分要求
                    //模糊 调nwdn api 进行超分(20MB 2048*2048)
                    $canAck = $this->createTask($imageKey,$imageUrl,$v,$erpType,$imgLink);
                }else{
                    //无需超分 返回原图
                    $this->noticeErp($imageKey,$imageUrl,$erpRequestInfo,$queueDatas,'notice erp (image is not blur)');
                }
            }
        }else{
            //获取图片base64失败
            $canAck = false;
        }
        return $canAck;
    }

    /**
     * 通过图片url获取二进制流
     * 使用封装的imagick压缩函数进行图片压缩(2MB && 2048*2048)
     * 将压缩后的二进制流上传至七牛，获取图片url
     * 上传七牛失败 --通知ERP
     * 上传七牛成功 进行模糊处理
     * @param $imageKey
     * @param $imageUrl
     * @param $erpRequestInfo
     * @param $queueDatas
     * @param $v
     * @param $erpType
     * @return bool
     */
    public function largeImage($imageKey,$imageUrl,$erpRequestInfo,$queueDatas,$v,$erpType){
        $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
        $limitSize = 2*$sizeTrillion;
        $canAck = true;
        $imgLink = '';
        $this->app->core->api->Log()->writeLog($imageUrl,'get large image blob ',$this->accessLog,$this->accessFunc);
        //获取二进制流
        $imageBlob = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
        if(empty($imageBlob)){
            $this->app->core->api->Log()->writeLog('','file_get_content large image failed',$this->errorLog,$this->errorFunc);
            $canAck = false;
        }else{
            $this->app->core->api->Log()->writeLog([$imageUrl,$limitSize],'compress image start with',$this->accessLog,$this->accessFunc);
            //压缩图片 2MB h:2048 w:2048
            $imageCompressRet = $this->app->core->api->Image()->compressImage($imageBlob,$limitSize,$this->nwdnLimit,$this->nwdnLimit);
            $this->app->core->api->Log()->writeLog('','compress image end with',$this->accessLog,$this->accessFunc);//$imageCompressRet中base64和二进制数据 记录日志会导致。。。
            //判断模糊条件 图片上限1MB
            if(empty($imageCompressRet['image_blob'])){
                $this->app->core->api->Log()->writeLog('','image compress result blob is empty',$this->errorLog,$this->errorFunc);
                $canAck = false;
            }else{
                //传七牛获取图片地址
                $blob = $imageCompressRet['image_blob'] ?? '';
                $pathParts = pathinfo($imageUrl);
                $imageOriginalName = $pathParts['filename'];//获取图片名称
                $imageName = 'erp/compress/'.date('Ymd').'/'.$imageOriginalName.'.jpg';
                $this->app->core->api->Log()->writeLog('','upload compress image blob to qiniu start',$this->accessLog,$this->accessFunc);
                $qiniuUploadRet = $this->app->admin->core->api->Qiniu()->uploadBlobToQiniu($blob,$imageName);
                $this->app->core->api->Log()->writeLog($qiniuUploadRet,'upload compress image blob to qiniu end with',$this->accessLog,$this->accessFunc);
                if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
                    $imgLink = $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url'];
                }else{
                    $imgLink = '';
                }
            }
        }
        if(!empty($imgLink)){
            $canAck = $this->fuzzyRecognition($imgLink,$imageKey,$imageUrl,$erpRequestInfo,$queueDatas,$v,$erpType);
        }else{
            //上传七牛出现问题
            $this->noticeErp($imageKey,$imageUrl,$erpRequestInfo,$queueDatas,'notice erp (upload large image failed)');
        }
        return $canAck;
    }

    /**
     * 消费数据
     * 判断图片是否为七牛或其cdn的图片 否--通知ERP
     * 是其cdn的替换url
     * 使用七牛图片信息样式符 读取图片信息
     * 1）图片满足 2MB && 2048*2048 模糊识别处理
     * 2）图片过大 样式符失效 使用封装imagick压缩上传至七牛 重复1）中的模糊识别处理
     * 3）其他情况 --通知ERP
     * @param $erpRequestInfo
     * @param $queueDatas
     * @param $v
     * @param $erpType
     */
    private function consumerSingleData($erpRequestInfo,$queueDatas,$v,$erpType){
        $canAck = true;
        if(!empty($v['photo_ai']) && !empty($v['unique_code'])){
            $imageKey = $v['unique_code'];
            $imageUrl = $v['photo_ai'];
            $this->app->core->api->Log()->writeLog($v,'single deal imageKey&imageUrl ',$this->accessLog,$this->accessFunc);
            $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
            $imageUrlData = parse_url($imageUrl);
            //图片地址跟七牛CDN关联的
            if(!empty($imageUrlData['host'])){
                if(in_array($imageUrlData['host'],$this->qiniuHost) || in_array($imageUrlData['host'],$this->otherSupportHost)){
                    //七牛图片 和 其他支持的host
                    if(isset($this->matchQiniuHost[$imageUrlData['host']])) {
                        $this->app->core->api->Log()->writeLog('', 'image host ' . $this->replaceHost[$imageUrlData['host']] . ' is replaced by qiniu host ' . $this->matchQiniuHost[$imageUrlData['host']], $this->accessLog, $this->accessFunc);
                        $imageUrl = str_replace($this->replaceHost[$imageUrlData['host']], $this->matchQiniuHost[$imageUrlData['host']], $imageUrl);
                    }
                    //通过七牛样式符处理
                    $imgLink = $imageUrl.$this->qiniuThumbnailKey;
                    $imgInfo = $imageUrl.$this->qiniuThumbnailInfoKey;
                    //通过七牛样式符获取图片信息
                    $imageOriginalInfo = $this->app->core->api->Image()->getInfo($imgInfo);
                    $size = 2*$sizeTrillion;//2MB
                    if(!empty($imageOriginalInfo) && isset($imageOriginalInfo['size']) && isset($imageOriginalInfo['width']) && isset($imageOriginalInfo['height'])
                        && ($imageOriginalInfo['size']<$size && $imageOriginalInfo['width']<=$this->nwdnLimit && $imageOriginalInfo['height']<=$this->nwdnLimit)){
                        //满足face++要求 亦满足超分要求 2M 2048*2048
                        $canAck = $this->fuzzyRecognition($imgLink,$imageKey,$imageUrl,$erpRequestInfo,$queueDatas,$v,$erpType);

                    }elseif(isset($imageOriginalInfo['error']) && strpos($imageOriginalInfo['error'],'too large')!==false){
                        //如果图片过大，使用自己封装的压缩处理
                        $canAck = $this->largeImage($imageKey,$imageUrl,$erpRequestInfo,$queueDatas,$v,$erpType);
                    }else{
                        //未读到图片信息 || 图片七牛样式符压缩完还是不能满足超分条件
                        $this->noticeErp($imageKey,$imageUrl,$erpRequestInfo,$queueDatas,'notice erp (image is error)');
                    }
                }else{
                    //不支持的host 原图返回
                    $this->noticeErp($imageKey,$imageUrl,$erpRequestInfo,$queueDatas,'notice erp (image_url is not support)');
                }
            }else{
                //获取图片host有误
                $this->app->core->api->Log()->writeLog($imageUrl,'imageUrl host is error ',$this->errorLog,$this->errorFunc);
            }
        }else{
            //缺少图片url或者唯一码，不处理
        }
        return $canAck;
    }

}
