<?php
namespace Core\Command;


use Face\Model\FaceppDetectImages;
use Face\Model\FaceppDetectSingleFace;
use MDK\Console\AbstractCommand;
use MDK\Console\CommandInterface;
use MDK\Console\ConsoleUtil;
use MDK\Exception;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use Nwdn\Model\NwdnCreateTaskLog;

/**
 * Test command.
 *
 * @CommandName(['reject_test'])
 * @CommandDescription('Test management.')
 */
class TestReject extends AbstractCommand implements CommandInterface{
    protected $imageMaxSize = 1048576;//1048576bytes = 1MB
    public $accessLog = 'consumer_erp_reject_access';
    public $errorLog = 'consumer_erp_reject_error';
    public $normalErrorLog = 'consumer_erp_reject_error';
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
    public $repeatCount = 5;
    public function fuzzyAction(){
        $imageDir = "D:\QQDownloads\init201-300";
        $datas = scandir($imageDir);
        $i = 0;
        error_log("---0.5---"."\n",3,$imageDir.DIRECTORY_SEPARATOR.'init_fuzzy.log');
        foreach ($datas as $v){
            if($v !="."  && $v !=".." && $v !="init_fuzzy.log" && $v !="init_fuzzy1.log"&& $v !="init_fuzzy0.log"&& $v !="init_fuzzy3.log"&& $v !="init_fuzzy4.log"){
                $i++;
                var_dump($i);
                var_dump($v);
                sleep(1);
                $image = $imageDir.DIRECTORY_SEPARATOR.$v;
                $imageBlobOriginal = $this->app->core->api->Image()->getBlobByImageUrl($image);
                $limitSize = $this->app->core->api->Image()->sizeTrillion;
                $compressRet = $this->app->core->api->Image()->compressImage($imageBlobOriginal,$limitSize);
                if(!empty($compressRet)){
                    //调腾讯模板识别api，要求图片小于1MB
                    $data = $this->app->tencent->api->Helper()->isFuzzy($compressRet['image_base64str']);
                    if(!empty($data) && isset($data['fuzzy'])){
                        if($data['fuzzy']){
                            error_log($v.' fuzzy ret is'.json_encode($data)."\n",3,$imageDir.DIRECTORY_SEPARATOR.'init_fuzzy.log');
                        }else{
//                            error_log($v.' fuzzy ret is'.json_encode($data)."\n",3,$imageDir.DIRECTORY_SEPARATOR.'init_fuzzy4.log');
                        }
                    }
                }
            }
        }
        error_log("\n",3,$imageDir.DIRECTORY_SEPARATOR.'init_fuzzy.log');
        exit;
    }
    /**
     * 消费ERP队列中的数据
     */
    public function syncAction(){

        /*
        $data ='{"taskid":"cc36491d6aed8041fa9c0c82fd27ba0b","phase":7,"input_url":"http:\/\/admin.soufeel.com\/share\/custom_product_photos\/original\/20190820\/20190820154336UEMF7q-SCQB06-1.png","sequence":56254,"source":"app","create_time":"2019-08-21 16:10:17","queue_wait_num":107,"author_id":"vip_aws","output_url":"https:\/\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\/seekings2\/cc36491d6aed8041fa9c0c82fd27ba0b.jpg","diff_url":[{"coords":[[222.49046325684,-68.946197509766],[108.77479553223,158.46731567383],[336.18829345703,272.18298339844],[449.90399169922,44.769470214844]],"url":"https:\/\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\/seekings2\/cc36491d6aed8041fa9c0c82fd27ba0b_face_0.jpg"},{"coords":[[234.94630432129,86.66886138916],[269.29431152344,304.85467529297],[487.48010253906,270.50668334961],[453.13208007812,52.320869445801]],"url":"https:\/\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\/seekings2\/cc36491d6aed8041fa9c0c82fd27ba0b_face_1.jpg"}],"max_face_num":20,"all_face_num":2,"state":1,"create":false}';
        $data = json_decode($data,true);
        $logTaskRet = $this->app->nwdn->api->Helper()->logTask($data);
        exit;
        */
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
                $msg = $message->body;
                $datas = json_decode($msg, true);
                var_dump($datas);
                sleep(2);
//                $this->app->core->api->Log()->writeLog('consumer end',' reject_ack  ',$this->accessLog,$this->accessFunc);
                if ($datas['abc'] == 'good') {
                    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                } else {
                    //数据出错，重新放回队列
                    if(empty($datas['repeat'])){
                        $datas['repeat'] = 1;
                    }else{
                        if($datas['repeat'] > $this->repeatCount){
                            var_dump('too many repeat');
                        }
                        $datas['repeat']++;
                    }
                    $publishData = json_encode($datas);
//                    $this->app->core->api->Log()->writeLog($publishData,' reject_ack  consumer end ,will restart',$this->accessLog,$this->accessFunc);
                    $publishConnInfo = $this->localRmq;
                    sleep(2);
//                    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
//                    $this->app->core->api->RabbitmqPublisher()->publish($publishConnInfo,$this->consumerExchangeInfo,$publishData);
                    $this->app->core->api->RabbitmqPublisher()->publish($publishConnInfo,$this->consumerExchangeInfo,$publishData,[],0,'topic');
                    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
//                    sleep(3);
//                    var_dump('nack');
//                    $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag'],false,true);
                }
                // Send a message with the string "quit" to cancel the consumer.
                if ($message->body === 'quit') {
                    $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
                }
            }catch (\Exception $e){
//                var_dump($e->getMessage());
                $this->app->core->api->Log()->writeLog($e->getMessage(),'consumer have exception',$this->errorLog,$this->errorFunc);
            }
        };
        return $callback;
        /*
         *
         * cat var/logger/consumer_erp_access.log | grep -A10 'H190816144810057'
         {"data":{"queue_data":"{\"0\":{\"photo_ai\":\"https:\\\/\\\/spic.qn.cdn.imaiyuan.com\\\/myphotowallet_20190801172546YYAdxK.png\",\"unique_code\":\"T190801184710003\",\"item_id\":\"852\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
H190816144810057|T190816163010048|T190816165010065|H190816204810060|J190816230010007|T190818044010077|H190819164810047|J190819170010005|J190819190010005|J190819200010009|H190820054810091|J190820170010009|H190820234810076
        {"data":{"queue_data":"{\"0\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190816\\\/20190816135512S3I5Qw-CQB30-1.png\",\"unique_code\":\"H190816144810057\",\"item_id\":\"7180579\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"0\":{\"photo_ai\":\"http:\\\/\\\/admin.soufeel.com\\\/share\\\/custom_product_photos\\\/original\\\/20190816\\\/20190816075518bC8V4i-SCQB05-1.png\",\"unique_code\":\"T190816163010048\",\"item_id\":\"7180972\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"0\":{\"photo_ai\":\"http:\\\/\\\/admin.soufeel.com\\\/share\\\/custom_product_photos\\\/original\\\/20190816\\\/201908160831463CvO3O-CQB11-1.png\",\"unique_code\":\"T190816165010065\",\"item_id\":\"7181006\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"0\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190809\\\/20190809012700d2pIZw-CQB30-1.png\",\"unique_code\":\"H190816204810060\",\"item_id\":\"7181763\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"0\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190816\\\/20190816212431V3Zqag-SCQB01-1.png\",\"unique_code\":\"J190816230010007\",\"item_id\":\"7182263\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"1\":{\"photo_ai\":\"http:\\\/\\\/admin.soufeel.com\\\/share\\\/custom_product_photos\\\/original\\\/20190817\\\/20190817193739Es7L6e-SCQB06-1.png\",\"unique_code\":\"T190818044010077\",\"item_id\":\"7188256\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"2\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190819\\\/20190819161921STU5Yw-CQB30-1.png\",\"unique_code\":\"H190819164810047\",\"item_id\":\"7195163\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"2\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190819\\\/20190819162318Z2JsYQ-CQB30-1.png\",\"unique_code\":\"J190819170010005\",\"item_id\":\"7195258\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"0\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190819\\\/20190819172942TmhHYg-CQB30-1.png\",\"unique_code\":\"J190819190010005\",\"item_id\":\"7195528\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"1\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190819\\\/20190819173651R1V3Mw-CQB38-1.png\",\"unique_code\":\"J190819200010009\",\"item_id\":\"7195654\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"1\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190820\\\/20190820051141YjlYLw-CQB30-1.png\",\"unique_code\":\"H190820054810091\",\"item_id\":\"7197535\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"0\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190820\\\/20190820161008SW9BeQ-SCQB06-1.png\",\"unique_code\":\"J190820170010009\",\"item_id\":\"7200195\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}
        {"data":{"queue_data":"{\"0\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190820\\\/20190820233632M2c3dw-CQB30-1.png\",\"unique_code\":\"H190820234810076\",\"item_id\":\"7201316\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}

        {"data":{"queue_data":"{\"2\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190819\\\/20190819162318Z2JsYQ-CQB30-1.png\",\"unique_code\":\"J190819170010005\",\"item_id\":\"7195258\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}

                 *
         *
         *
         * */
    }

    public function publishAction(){
        $publishConnInfo = $this->localRmq;
        $publishData = '{"abc":"bad1","repeat":10086}';
        var_dump($publishConnInfo,$this->consumerExchangeInfo,$publishData);
        $this->app->core->api->RabbitmqPublisher()->publish($publishConnInfo,$this->consumerExchangeInfo,$publishData,[],0,'topic');
    }

    public function updateTaskLogAction(){
        $data ='{"taskid":"cc36491d6aed8041fa9c0c82fd27ba0b","phase":7,"input_url":"http:\/\/admin.soufeel.com\/share\/custom_product_photos\/original\/20190820\/20190820154336UEMF7q-SCQB06-1.png","sequence":56254,"source":"app","create_time":"2019-08-21 16:10:17","queue_wait_num":107,"author_id":"vip_aws","output_url":"https:\/\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\/seekings2\/cc36491d6aed8041fa9c0c82fd27ba0b.jpg","diff_url":[{"coords":[[222.49046325684,-68.946197509766],[108.77479553223,158.46731567383],[336.18829345703,272.18298339844],[449.90399169922,44.769470214844]],"url":"https:\/\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\/seekings2\/cc36491d6aed8041fa9c0c82fd27ba0b_face_0.jpg"},{"coords":[[234.94630432129,86.66886138916],[269.29431152344,304.85467529297],[487.48010253906,270.50668334961],[453.13208007812,52.320869445801]],"url":"https:\/\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\/seekings2\/cc36491d6aed8041fa9c0c82fd27ba0b_face_1.jpg"}],"max_face_num":20,"all_face_num":2,"state":1,"create":false}';
        $data = json_decode($data,true);
        $logTaskRet = $this->app->nwdn->api->Helper()->logTask($data);
        exit;
    }

    public function faceDirectAction(){


        $imageDir = 'D:\image\image_template\BH450.jpg';
//        $imageDir = 'D:\image\image_0904_niren\dpY6GRDfreQf.png';
        $imageDir = 'D:\image\image_0904_niren\image_20190904094849.jpg';
//        $imageDir = 'D:\image\image_0904_niren\Rk4DHCnhix52.png';
        $imageDir = 'https://spic.qn.cdn.imaiyuan.com/custom_product_photos/original/20191009/20191009151959N0NBVQ-CQB25-1.png-soufeel_super_image_ai';
        $imageBlobOriginal = $this->app->core->api->Image()->getBlobByImageUrl($imageDir);
        $limitSize = $this->app->core->api->Image()->sizeTrillion;
        $limitSize = 2*$limitSize;
        //调face++api，要求图片小于2MB 4096
        $compressRet = $this->app->core->api->Image()->compressImage($imageBlobOriginal,$limitSize,2000,2000);
        $params = [
            'image_base64' => $compressRet['image_base64str']
        ];
        $data = $this->app->face->api->Helper()->faceDetect($params);
        var_dump($data);
        $this->app->core->api->Log()->writeLog($data,' face detect',$this->accessLog,$this->errorFunc);
//        var_dump($data['faces'][0]['face_rectangle']);
        exit;
        $start = time();
        $imageDir = "D:\QQDownloads\init_test_face\init_1036";
        $datas = scandir($imageDir);
        $i = 0;
        $imageBlurName = [];
        foreach ($datas as $v){
            if($v !="."  && $v !=".." && $v !="init_face.log" && $v !="init_face_str.log" && ($v=="129.png" || $v=="142.png" || $v=="274.png" || $v=="302.png" || $v=="476.png" || $v=="668.png" || $v=="860.png" || $v=="973.png")){
                $i++;
                var_dump($i);
                var_dump($v);
                $image = $imageDir.DIRECTORY_SEPARATOR.$v;
                $imageBlobOriginal = $this->app->core->api->Image()->getBlobByImageUrl($image);
                $limitSize = $this->app->core->api->Image()->sizeTrillion;
                $limitSize = 2*$limitSize;
                $compressRet = $this->app->core->api->Image()->compressImage($imageBlobOriginal,$limitSize,4000,4000);
                if(!empty($compressRet)){
                    //保存图片
//                    $tmp = str_replace('.','-4000.',$v);
//                    $filename = $imageDir.DIRECTORY_SEPARATOR.$tmp;
//                    $img = base64_decode($compressRet['image_base64str']);
//                    file_put_contents($filename, $img);
                    //调face++api，要求图片小于2MB 4096
                    $data = $this->app->face->api->Helper()->faceDetect($compressRet['image_base64str']);
                    if(!empty($data)){
                        $faceNum = $data['face_num'] ?? 0;
                        $blurValue = 0;
                        $faceValue = 0;
                        $blurData = [];
                        $blurStr = '';
                        if(!empty($data['faces'])){
                            foreach ($data['faces'] as $df){
                                if(isset($df['attributes']['blur'])){
                                    if($df['attributes']['blur']['blurness']['value'] ==$df['attributes']['blur']['motionblur']['value'] &&$df['attributes']['blur']['gaussianblur']['value'] == $df['attributes']['blur']['motionblur']['value']){
                                        $blurData[] = $df['attributes']['blur']['blurness']['value'];
                                        if($df['attributes']['blur']['blurness']['value'] > 50){
                                            $blurValue = -1;
                                        }else{
                                            if($blurValue != -1){
                                                $blurValue = 1;
                                            }
                                        }
                                        if($df['attributes']['facequality']['value'] <70.1){
                                            $faceValue = -1;
                                        }else{
                                            if($faceValue != -1){
                                                $faceValue = 1;
                                            }
                                        }
                                        $blurStr .=$df['attributes']['blur']['blurness']['value'].';';
//                                        $blurStr .=$df['attributes']['facequality']['threshold'].';';
                                        $blurStr .=$df['attributes']['facequality']['value'].';';
                                    }else{
                                        //facequality
                                        $blurData[] = [
                                            'blurness' => $df['attributes']['blur']['blurness']['value'] ?? 0,
                                            'motionblur' => $df['attributes']['blur']['motionblur']['value'] ?? 0,
                                            'gaussianblur' => $df['attributes']['blur']['gaussianblur']['value'] ?? 0,
                                        ];
                                        $blurStr .=$df['attributes']['blur']['blurness']['value'].';';
                                        $blurStr .=$df['attributes']['blur']['motionblur']['value'].';';
                                        $blurStr .=$df['attributes']['blur']['gaussianblur']['value'].';';
//                                        $blurStr .=$df['attributes']['facequality']['threshold'].';';
                                        $blurStr .=$df['attributes']['facequality']['value'].';';
                                    }
                                    if($df['attributes']['blur']['blurness']['value']>1 ||$df['attributes']['blur']['motionblur']['value']>1 ||$df['attributes']['blur']['gaussianblur']['value']>1){
                                        $imageBlurName[$v] = true;
                                    }
                                }
                            }
                            if($blurValue == 0){
                                $tmpBlur = '空;';
                            }elseif($blurValue == -1){
                                $tmpBlur = '不合格;';
                            }elseif($blurValue == 1){
                                $tmpBlur = '合格;';
                            }
                            if($faceValue == 0){
                                $tmpFace = '空;';
                            }elseif($faceValue == -1){
                                $tmpFace = '不合格;';
                            }elseif($faceValue == 1){
                                $tmpFace = '合格;';
                            }
                            $blurStr = $tmpBlur.$tmpFace.$blurStr;
                        }else{
                            if($faceNum == 0){
                                $blurStr .= '空;';
                                $blurStr .= '空;';
                            }
                        }
                        error_log($v.';'.$faceNum.';'.json_encode($blurData)."\n",3,$imageDir.DIRECTORY_SEPARATOR.'init_face.log');
                        error_log($v.';'.$faceNum.';'.$blurStr."\n",3,$imageDir.DIRECTORY_SEPARATOR.'init_face_str.log');
                    }else{
                        var_dump('face failed');
                    }
                }else{
                    var_dump('compress failed');
                }
            }
        }
        foreach ($imageBlurName as $iname=>$iv){
            error_log($iname.' is blur'."\n",3,$imageDir.DIRECTORY_SEPARATOR.'init_face.log');
        }
        echo "\n";
        $end = time();
        var_dump($end-$start);
        var_dump('end');
        exit;
        $image = '';
        $data = $this->app->face->api->Helper()->faceDetect($image);
        var_dump($data);
    }

    public function testAction(){
        $start = time();
        $imageDir = "D:\QQDownloads\init_test_face\iabc";
        $datas = scandir($imageDir);
        $i = 0;
        $imageBlurName = [];
        foreach ($datas as $v){
            if($v !="."  && $v !=".." && $v !="init_face.log" && $v !="init_face_str.log" && ($v == '64.png'|| $v=='75.png'|| $v=='77.png'|| $v=='83.png'|| $v=='91.png'|| $v=='119.png') ){
                $i++;
                var_dump($i);
                var_dump($v);
                $image = $imageDir.DIRECTORY_SEPARATOR.$v;
                $imageBlobOriginal = $this->app->core->api->Image()->getBlobByImageUrl($image);
                $limitSize = $this->app->core->api->Image()->sizeTrillion;
                $limitSize = 2*$limitSize;
                $compressRet = $this->app->core->api->Image()->compressImage($imageBlobOriginal,$limitSize,4000,4000);
                if(!empty($compressRet)){
                    //保存图片
                    $tmp = str_replace('.','-4000.',$v);
                    $filename = $imageDir.DIRECTORY_SEPARATOR.$tmp;
                    $img = base64_decode($compressRet['image_base64str']);
                    file_put_contents($filename, $img);
                    //调face++api，要求图片小于2MB 4096
                    $data = $this->app->face->api->Helper()->faceDetect($compressRet['image_base64str']);
                    if(!empty($data)){
                        $faceNum = $data['face_num'] ?? 0;
                        $blurData = [];
                        $blurStr = '';
                        if(!empty($data['faces'])){
                            foreach ($data['faces'] as $df){
                                if(isset($df['attributes']['blur'])){
                                    if($df['attributes']['blur']['blurness']['value'] ==$df['attributes']['blur']['motionblur']['value'] &&$df['attributes']['blur']['gaussianblur']['value'] == $df['attributes']['blur']['motionblur']['value']){
                                        $blurData[] = $df['attributes']['blur']['blurness']['value'];
                                        if($df['attributes']['blur']['blurness']['value'] > 50){
                                            $blurStr .= '不合格;';
                                        }else{
                                            $blurStr .= '合格;';
                                        }
                                        if($df['attributes']['facequality']['value'] <70.1){
                                            $blurStr .= '不合格;';
                                        }else{
                                            $blurStr .= '合格;';
                                        }
                                        $blurStr .=$df['attributes']['blur']['blurness']['value'].';';
                                        $blurStr .=$df['attributes']['facequality']['threshold'].';';
                                        $blurStr .=$df['attributes']['facequality']['value'].';';
                                    }else{
                                        //facequality
                                        $blurData[] = [
                                            'blurness' => $df['attributes']['blur']['blurness']['value'] ?? 0,
                                            'motionblur' => $df['attributes']['blur']['motionblur']['value'] ?? 0,
                                            'gaussianblur' => $df['attributes']['blur']['gaussianblur']['value'] ?? 0,
                                        ];
                                        $blurStr .=$df['attributes']['blur']['blurness']['value'].';';
                                        $blurStr .=$df['attributes']['blur']['motionblur']['value'].';';
                                        $blurStr .=$df['attributes']['blur']['gaussianblur']['value'].';';
                                        $blurStr .=$df['attributes']['facequality']['threshold'].';';
                                        $blurStr .=$df['attributes']['facequality']['value'].';';
                                    }
                                    if($df['attributes']['blur']['blurness']['value']>1 ||$df['attributes']['blur']['motionblur']['value']>1 ||$df['attributes']['blur']['gaussianblur']['value']>1){
                                        $imageBlurName[$v] = true;
                                    }
                                }
                            }
                        }else{
                            if($faceNum == 0){
                                $blurStr .= '空;';
                                $blurStr .= '空;';
                            }
                        }
                        error_log($v.';'.$faceNum.';'.json_encode($blurData)."\n",3,$imageDir.DIRECTORY_SEPARATOR.'init_face.log');
                        error_log($v.';'.$faceNum.';'.$blurStr."\n",3,$imageDir.DIRECTORY_SEPARATOR.'init_face_str.log');
                    }
                }
            }
        }
        foreach ($imageBlurName as $iname=>$iv){
            error_log($iname.' is blur'."\n",3,$imageDir.DIRECTORY_SEPARATOR.'init_face.log');
        }
        echo "\n";
        $end = time();
        var_dump($end-$start);
        var_dump('end');
        exit;
        $msg = '{"data":{"queue_data":"{\"0\":{\"photo_ai\":"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20190816\\\/20190816135512S3I5Qw-CQB30-1.png\",\"unique_code\":\"H190816144810057\",\"item_id\":\"7180579\"},\"type\":\"MERP\"}","task_type":"ajmagedownload","taskmgr_sign":"A373E0A089753D8A342F5AF7CFA5EB1E"},"url":"http:\/\/v2.merp.com\/index.php\/openapi\/autotask\/service\/"}';
        $datas = json_decode($msg, true);
        var_dump($datas);
    }

    public function renameAction(){
        $imageDir = "D:\QQDownloads\init_test_face\init_1036";
        $datas = scandir($imageDir);
        $i = 0;
        foreach ($datas as $v){
            if($v !="."  && $v !=".." && $v !="init_face.log" ) {
                $i++;
                var_dump($i);
                $tmp = pathinfo($v);
                $ext= $tmp['extension'];
                $newName = $i.'.'.$ext;
                rename($imageDir.DIRECTORY_SEPARATOR.$v,$imageDir.DIRECTORY_SEPARATOR.$newName);
            }
        }
    }
    public function noticeErpAction(){
        //pro
        $postData =        [
            'base_uri' => 'http://120.76.221.184/',
            'app_id' => 'MERPCP190805',
            'sign' => '2987B91FF99B16EF0A8D953E015F4ECE',
            'method' => 'sync_uc_clarity',
        ];
        $imageUrl = 'http://admin.soufeel.com/share/custom_product_photos/original/20190817/20190817193739Es7L6e-SCQB06-1.png';
        $imageKey = 'T190818044010077';
        $postData['params'] = json_encode([$imageKey=>$imageUrl,'clarity_type' => 0]);
        $this->app->core->api->Log()->writeLog($postData,'notice erp start with(image is not fuzzy)',$this->accessLog,$this->accessFunc);
        $erpRet = $this->app->core->api->Erp()->notice($postData);
        $this->app->core->api->Log()->writeLog($erpRet,'notice erp end with(image is not fuzzy)',$this->accessLog,$this->accessFunc);
    }

    public function findUndealAction(){
        $arr = [1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            9,
            10,
            11,
            12,
            13,
            14,
            15,
            16,
            17,
            18,
            19,
            20,
            21,
            22,
            23,
            24,
            25,
            26,
            27,
            28,
            29,
            30,
            31,
            32,
            33,
            34,
            35,
            36,
            37,
            38,
            39,
            40,
            41,
            42,
            43,
            44,
            45,
            46,
            47,
            48,
            49,
            50,
            51,
            52,
            53,
            54,
            55,
            56,
            57,
            58,
            59,
            60,
            61,
            62,
            63,
            64,
            65,
            66,
            67,
            68,
            69,
            70,
            71,
            72,
            73,
            74,
            75,
            76,
            77,
            78,
            79,
            80,
            81,
            82,
            83,
            84,
            85,
            86,
            87,
            88,
            89,
            90,
            91,
            92,
            93,
            94,
            95,
            96,
            97,
            98,
            99,
            100,
            101,
            102,
            103,
            104,
            105,
            106,
            107,
            108,
            109,
            110,
            111,
            112,
            113,
            114,
            115,
            116,
            117,
            118,
            119,
            120,
            121,
            122,
            123,
            124,
            125,
            126,
            127,
            128,
            130,
            131,
            132,
            133,
            134,
            135,
            136,
            137,
            138,
            139,
            140,
            141,
            143,
            144,
            145,
            146,
            147,
            148,
            149,
            150,
            151,
            152,
            153,
            154,
            155,
            156,
            157,
            158,
            159,
            160,
            161,
            162,
            163,
            164,
            165,
            166,
            167,
            168,
            169,
            170,
            171,
            172,
            173,
            174,
            175,
            176,
            177,
            178,
            179,
            180,
            181,
            182,
            183,
            184,
            185,
            186,
            187,
            188,
            189,
            190,
            191,
            192,
            193,
            194,
            195,
            196,
            197,
            198,
            199,
            200,
            201,
            202,
            203,
            204,
            205,
            206,
            207,
            208,
            209,
            210,
            211,
            212,
            213,
            214,
            215,
            216,
            217,
            218,
            219,
            220,
            221,
            222,
            223,
            224,
            225,
            226,
            227,
            228,
            229,
            230,
            231,
            232,
            233,
            234,
            235,
            236,
            237,
            238,
            239,
            240,
            241,
            242,
            243,
            244,
            245,
            246,
            247,
            248,
            249,
            250,
            251,
            252,
            253,
            254,
            255,
            256,
            257,
            258,
            259,
            260,
            261,
            262,
            263,
            264,
            265,
            266,
            267,
            268,
            269,
            270,
            271,
            272,
            273,
            275,
            276,
            277,
            278,
            279,
            280,
            281,
            282,
            283,
            284,
            285,
            286,
            287,
            288,
            289,
            290,
            291,
            292,
            293,
            294,
            295,
            296,
            297,
            298,
            299,
            300,
            301,
            303,
            304,
            305,
            306,
            307,
            308,
            309,
            310,
            311,
            312,
            313,
            314,
            315,
            316,
            317,
            318,
            319,
            320,
            321,
            322,
            323,
            324,
            325,
            326,
            327,
            328,
            329,
            330,
            331,
            332,
            333,
            334,
            335,
            336,
            337,
            338,
            339,
            340,
            341,
            342,
            343,
            344,
            345,
            346,
            347,
            348,
            349,
            350,
            351,
            352,
            353,
            354,
            355,
            356,
            357,
            358,
            359,
            360,
            361,
            362,
            363,
            364,
            365,
            366,
            367,
            368,
            369,
            370,
            371,
            372,
            373,
            374,
            375,
            376,
            377,
            378,
            379,
            380,
            381,
            382,
            383,
            384,
            385,
            386,
            387,
            388,
            389,
            390,
            391,
            392,
            393,
            394,
            395,
            396,
            397,
            398,
            399,
            400,
            401,
            402,
            403,
            404,
            405,
            406,
            407,
            408,
            409,
            410,
            411,
            412,
            413,
            414,
            415,
            416,
            417,
            418,
            419,
            420,
            421,
            422,
            423,
            424,
            425,
            426,
            427,
            428,
            429,
            430,
            431,
            432,
            433,
            434,
            435,
            436,
            437,
            438,
            439,
            440,
            441,
            442,
            443,
            444,
            445,
            446,
            447,
            448,
            449,
            450,
            451,
            452,
            453,
            454,
            455,
            456,
            457,
            458,
            459,
            460,
            461,
            462,
            463,
            464,
            465,
            466,
            467,
            468,
            469,
            470,
            471,
            472,
            473,
            474,
            475,
            477,
            478,
            479,
            480,
            481,
            482,
            483,
            484,
            485,
            486,
            487,
            488,
            489,
            490,
            491,
            492,
            493,
            494,
            495,
            496,
            497,
            498,
            499,
            500,
            501,
            502,
            503,
            504,
            505,
            506,
            507,
            508,
            509,
            510,
            511,
            512,
            513,
            514,
            515,
            516,
            517,
            518,
            519,
            520,
            521,
            522,
            523,
            524,
            525,
            526,
            527,
            528,
            529,
            530,
            531,
            532,
            533,
            534,
            535,
            536,
            537,
            538,
            539,
            540,
            541,
            542,
            543,
            544,
            545,
            546,
            547,
            548,
            549,
            550,
            551,
            552,
            553,
            554,
            555,
            556,
            557,
            558,
            559,
            560,
            561,
            562,
            563,
            564,
            565,
            566,
            567,
            568,
            569,
            570,
            571,
            572,
            573,
            574,
            575,
            576,
            577,
            578,
            579,
            580,
            581,
            582,
            583,
            584,
            585,
            586,
            587,
            588,
            589,
            590,
            591,
            592,
            593,
            594,
            595,
            596,
            597,
            598,
            599,
            600,
            601,
            602,
            603,
            604,
            605,
            606,
            607,
            608,
            609,
            610,
            611,
            612,
            613,
            614,
            615,
            616,
            617,
            618,
            619,
            620,
            621,
            622,
            623,
            624,
            625,
            626,
            627,
            628,
            629,
            630,
            631,
            632,
            633,
            634,
            635,
            636,
            637,
            638,
            639,
            640,
            641,
            642,
            643,
            644,
            645,
            646,
            647,
            648,
            649,
            650,
            651,
            652,
            653,
            654,
            655,
            656,
            657,
            658,
            659,
            660,
            661,
            662,
            663,
            664,
            665,
            666,
            667,
            669,
            670,
            671,
            672,
            673,
            674,
            675,
            676,
            677,
            678,
            679,
            680,
            681,
            682,
            683,
            684,
            685,
            686,
            687,
            688,
            689,
            690,
            691,
            692,
            693,
            694,
            695,
            696,
            697,
            698,
            699,
            700,
            701,
            702,
            703,
            704,
            705,
            706,
            707,
            708,
            709,
            710,
            711,
            712,
            713,
            714,
            715,
            716,
            717,
            718,
            719,
            720,
            721,
            722,
            723,
            724,
            725,
            726,
            727,
            728,
            729,
            730,
            731,
            732,
            733,
            734,
            735,
            736,
            737,
            738,
            739,
            740,
            741,
            742,
            743,
            744,
            745,
            746,
            747,
            748,
            749,
            750,
            751,
            752,
            753,
            754,
            755,
            756,
            757,
            758,
            759,
            760,
            761,
            762,
            763,
            764,
            765,
            766,
            767,
            768,
            769,
            770,
            771,
            772,
            773,
            774,
            775,
            776,
            777,
            778,
            779,
            780,
            781,
            782,
            783,
            784,
            785,
            786,
            787,
            788,
            789,
            790,
            791,
            792,
            793,
            794,
            795,
            796,
            797,
            798,
            799,
            800,
            801,
            802,
            803,
            804,
            805,
            806,
            807,
            808,
            809,
            810,
            811,
            812,
            813,
            814,
            815,
            816,
            817,
            818,
            819,
            820,
            821,
            822,
            823,
            824,
            825,
            826,
            827,
            828,
            829,
            830,
            831,
            832,
            833,
            834,
            835,
            836,
            837,
            838,
            839,
            840,
            841,
            842,
            843,
            844,
            845,
            846,
            847,
            848,
            849,
            850,
            851,
            852,
            853,
            854,
            855,
            856,
            857,
            858,
            859,
            861,
            862,
            863,
            864,
            865,
            866,
            867,
            868,
            869,
            870,
            871,
            872,
            873,
            874,
            875,
            876,
            877,
            878,
            879,
            880,
            881,
            882,
            883,
            884,
            885,
            886,
            887,
            888,
            889,
            890,
            891,
            892,
            893,
            894,
            895,
            896,
            897,
            898,
            899,
            900,
            901,
            902,
            903,
            904,
            905,
            906,
            907,
            908,
            909,
            910,
            911,
            912,
            913,
            914,
            915,
            916,
            917,
            918,
            919,
            920,
            921,
            922,
            923,
            924,
            925,
            926,
            927,
            928,
            929,
            930,
            931,
            932,
            933,
            934,
            935,
            936,
            937,
            938,
            939,
            940,
            941,
            942,
            943,
            944,
            945,
            946,
            947,
            948,
            949,
            950,
            951,
            952,
            953,
            954,
            955,
            956,
            957,
            958,
            959,
            960,
            961,
            962,
            963,
            964,
            965,
            966,
            967,
            968,
            969,
            970,
            971,
            972,
            974,
            975,
            976,
            977,
            978,
            979,
            980,
            981,
            982,
            983,
            984,
            985,
            986,
            987,
            988,
            989,
            990,
            991,
            992,
            993,
            994,
            995,
            996,
            997,
            998,
            999,
            1000,
            1001,
            1002,
            1003,
            1004,
            1005,
            1006,
            1007,
            1008,
            1009,
            1010,
            1011,
            1012,
            1013,
            1014,
            1015,
            1016,
            1017,
            1018,
            1019,
            1020,
            1021,
            1022,
            1023,
            1024,
            1025,
            1026,
            1027,
            1028,
            1029,
            1030,
            1031,
            1032,
            1033,
            1034,
            1035,
            1036,
        ];
        for($i=1;$i<=1036;$i++){
            if(!in_array($i,$arr)){
                echo ' || $v=="'.$i.'.png"';
            }
        }
    }

    public function findImageAction(){
        $data = [
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/20190731152606aCNcsd-SCQB06Z-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190801/20190801040515N2VZSQ-CQB34-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/20190731173717laUKr9-SCQB06Z-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190731/20190731235553N1N6bw-CQB08-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/20190731101731W2HQ9d-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/20190731153338eS9p53-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/20190731214132ktAkHP-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/20190731212739E2b4xh-SCQB06Z-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190801/20190801101746YmYwZQ-SCQB02-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/20190731182518ZvyswW-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190726/20190726170057AjyOdv-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190730/20190730135845y5NvRN-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/20190731150439fRN3zv-SCQB06Z-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190731/20190731124553T1QrQg-CQB30-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190802/20190802095204aWNTTg-CQB30-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190801/20190801163938RldZZA-CQB30-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190801/20190801163938RldZZA-CQB30-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190802/20190802051210L2xlMg-CQB30-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190802/20190802213650aTA4ag-CQB30-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190801/20190801143926VbkgnT-CQB38-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190802/201908020118481M7jBx-CQB38-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190731/20190731140337VDVudw-CQB30-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190801/20190801235415gbWp3u-SCQB06-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190801/20190801190104x4qf4s-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190801/20190801221903Ydzwjj-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190801/20190801231234GXxM3X-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190706/20190706112209BNlFzx-SCQB06-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190706/20190706112339LxYZrs-SCQB06-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190801/20190801131807xvUPe2-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190801/20190801162441TThS1i-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190801/20190801154927i6QZHV-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190802/20190802205642G5c1yX-CQB30-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190802/20190802131747W5dNVW-CQB30-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190802/20190802181524TfxL53-CQB30-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190802/20190802225320ZIJ3r6-CQB30-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190802/20190802142846MEmDLB-CQB30-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190802/20190802111511poo9IM-CQB30-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190803/20190803010127K2OkYL-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190802/20190802185240cFSn6H-SCQB02-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190802/20190802202512ekuFYe-SCQB06-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190801/201908012019420StpRV-SCQB06-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190803/20190803040323RNKlcv-SCQB06-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/201907311310488oQhpx-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/20190731170807v5knwE-SCQB06Z-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190802/20190802202444obxBwG-SCQB02-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190731/20190731144527t20OXW-SCQB06-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190803/20190803032021dm5jVA-CQB25-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190802/20190802184144Z0ZUUw-CQB30-1.png',
            'http://admin.soufeel.com/share/custom_product_photos/original/20190730/20190730163038NSXlbi-SCQB06Z-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190801/20190801083252b0s0Zw-CQB08-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190802/20190802152754MHNvUA-SCQB03-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190801/20190801125212MkdMVg-SCQB03-1.png',
            'https://pic.stylelab.com/share/custom_product_photos/original/20190803/20190803112228d0RtRw-SCQB03-1.png',
        ];

        var_dump(count($data));
        $data = array_unique($data);
        var_dump(count($data));
        $imageDir = "D:\QQDownloads\init53";
        foreach ($data as $k=>$j){
            $tmp = false;
            $datas = scandir($imageDir);
            foreach ($datas as $v){
                if($v !="."  && $v !=".."){
                    if(strpos($j,$v)){
                        $tmp = true;
                    }
                }
            }
            if($tmp == false){
                var_dump($j);
            }
        }

    }

    public function imageRenameTestAction(){
        $imageDir = "D:\QQDownloads\init53";
        $datas = scandir($imageDir);
        foreach ($datas as $v){
            if($v !="."  && $v !=".."){
                $vSize = filesize($imageDir.DIRECTORY_SEPARATOR.$v);
                $imageDirTest = "D:\QQDownloads\init_test_face\init_1036";
                $datasTest = scandir($imageDirTest);
                foreach ($datasTest as $j){
                    if($v !="."  && $v !=".."){
                        $jSize = filesize($imageDirTest.DIRECTORY_SEPARATOR.$j);
                        if($vSize == $jSize){
                            rename($imageDir.DIRECTORY_SEPARATOR.$v,$imageDir.DIRECTORY_SEPARATOR.$j);
                            break;
                        }
                    }

                }


            }

        }
    }

    public function testFuzzyAction(){
        $imageDir = "D:\itestLog";
        $datas = scandir($imageDir);
        echo 'us-test: '."\n";
        $i=0;
        foreach ($datas as $v) {
            if ($v != "." && $v != ".." ) {
                var_dump($v);
                $i++;echo $i."\n";
                $image = file_get_contents($imageDir.DIRECTORY_SEPARATOR.$v);
                $startTime = microtime();
                $startTime = explode(' ',$startTime);
                $startTimeSec = floatval($startTime[0]);
                $startTimeMicSec = floatval($startTime[1]);
                $startTime = $startTimeSec + $startTimeMicSec;
                echo 'start time :'.$startTime."\n";
                try{
                    //由调用腾讯api改为调用face++api 2MB 4096*4096
                    $blob = $this->app->core->api->Image()->getBlobByBase64($image);
                    $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
                    $limitSize = 2*$sizeTrillion;
                    $compressRet = $this->app->core->api->Image()->compressImage($blob,$limitSize,3000,3000);
                    if(empty($compressRet)){
                        $this->app->core->api->Log()->writeLog('','compress failed','fuzzy_compress_error','log');//
                        $data = [
                            'fuzzy' => false,
                            'msg' => ''
                        ];
                    }else{
                        $endTime = microtime();
                        $endTime = explode(' ',$endTime);
                        $endTimeSec = floatval($endTime[0]);
                        $endTimeMicSec = floatval($endTime[1]);
                        $endTime = $endTimeSec + $endTimeMicSec;
                        echo 'end1 time :'.$endTime."\n";
                        $usedTime = $endTime - $startTime;
                        echo 'used1 time :'.$usedTime."\n";
                        $data = $this->app->face->api->Helper()->faceDetect([ 'image_base64'=> $compressRet['image_base64str']]);
                        $this->app->core->api->Log()->writeLog($data,'face_detect' ,'fuzzy_compress_error','log');//
                        $fuzzy = $this->app->face->api->Helper()->isBlur($data);
                        $data = [
                            'fuzzy' => $fuzzy,
                            'msg' => $fuzzy ? 'Your selected image is blurry, we recommend that you change a clearer one.' :''
                        ];
                    }
                }catch (\Exception $e){
                    var_dump($e);
                }
                $endTime = microtime();
                $endTime = explode(' ',$endTime);
                $endTimeSec = floatval($endTime[0]);
                $endTimeMicSec = floatval($endTime[1]);
                $endTime = $endTimeSec + $endTimeMicSec;
                echo 'end2 time :'.$endTime."\n";
                $usedTime = $endTime - $startTime;
                echo 'used2 time :'.$usedTime."\n"."\n";
                $this->app->core->api->Log()->writeLog('',$i.'start time'.$startTime.' end time'.$endTime.' used time '.$usedTime ,'fuzzy_compress_error','log');//
                break;
            }
        }
        echo "end";
        exit;
        $image = $this->request->getParam('image');
        try{
//            $data = $this->app->tencent->api->Helper()->isFuzzy($image);
            //由调用腾讯api改为调用face++api 2MB 4096*4096
            $blob = $this->app->core->api->Image()->getBlobByBase64($image);
            $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
            $limitSize = 2*$sizeTrillion;
            $compressRet = $this->app->core->api->Image()->compressImage($blob,$limitSize,3000,3000);
            if(empty($compressRet)){
                $this->app->core->api->Log()->writeLog('','compress failed','fuzzy_compress_error','log');//
                $data = [
                    'fuzzy' => false,
                    'msg' => ''
                ];
            }else{
                $data = $this->app->face->api->Helper()->faceDetect([ 'image_base64'=> $compressRet['image_base64str']]);
                $fuzzy = $this->app->face->api->Helper()->isBlur($data);
                $data = [
                    'fuzzy' => $fuzzy,
                    'msg' => $fuzzy ? 'Your selected image is blurry, we recommend that you change a clearer one.' :''
                ];
            }
        }catch (\Exception $e){
            var_dump($e);
        }
        exit;
        $imageUrl = 'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190827/20190827000548xTVTC2-CQB08-1.png-soufeel_super_image_ai';
        $image = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
        $data = $this->app->face->api->Helper()->faceDetect([ 'image_base64'=> $image]);
        $fuzzy = $this->app->face->api->Helper()->isBlur($data);
        $data = [
            'fuzzy' => $fuzzy,
            'msg' => ''
        ];
        var_dump($data);
    }

    public function baseAction(){
        $data =[
            'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190828/20190828015402F16VNQ-CQB38-1.png-soufeel_super_image_ai',
            'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190827/201908272127334URYT6-SCQB06-1.png-soufeel_super_image_ai',
            'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190828/201908280300463RhSRy-CQB10-1.png-soufeel_super_image_ai',
            'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190828/20190828030623c3k3PK-SCQB02-1.png-soufeel_super_image_ai',
            'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190828/20190828041225vZhGnj-SCQB06-1.png-soufeel_super_image_ai',
            'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190828/20190828041518zyeLzD-CQB10-1.png-soufeel_super_image_ai',
            'https://spic.qn.cdn.imaiyuan.com/custom_product_photos/original/20190828/20190828120306RndPZg-CQB30-1.png-soufeel_super_image_ai',
            'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190828/2019082804492378Ozfc-SCQB06-1.png-soufeel_super_image_ai',
            'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190828/20190828050928SDq0Kf-SCQB06Z-1.png-soufeel_super_image_ai',
            'https://spic.qn.cdn.imaiyuan.com/custom_product_photos/original/20190828/20190828130058UGQxYw-CQB30-1.png-soufeel_super_image_ai',
            'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190820/20190820144455mLsJiS-SCQB06Z-1.png-soufeel_super_image_ai',
            'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190828/20190828064411Zw9ZDU-CQB30-1.png-soufeel_super_image_ai',
        ];
        $imageDir = "D:\QQDownloads\init53";
        foreach($data as $v){
            sleep(1);
            $time = time();
            $image = $this->app->core->api->Image()->getBase64ByImageUrl($v);
            error_log($image,3,$imageDir.DIRECTORY_SEPARATOR.$time.'.log');
        }
        echo "end";
    }


    public function testSqlAction(){
        $this->app->core->api->Log()->writeSkuLog('abc','abc');
        exit;
        $url = 'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190901/20190901170447XdiIrP-CQB08-1.png-soufeel_supe';
        $data = pathinfo($url);
        var_dump($data);
        exit;
        $faceTokens = '1e85bfd1dce7c4aa6fa1e84cc269429b';
        $data = $this->app->face->api->Helper()->analyze($faceTokens);
        var_dump($data);exit;
        $data = $this->app->face->api->Helper()->getFaceSets();
        var_dump($data);exit;
        $facesetToken = "827be7099bd1ece3bfcbffe73b611903";
        $faceTokens = '1e85bfd1dce7c4aa6fa1e84cc269429b';
        $data = $this->app->face->api->Helper()->addFace($facesetToken,$faceTokens);
        var_dump($data);exit;
        $data = $this->app->face->api->Helper()->createFaceSetToken();
        var_dump($data);exit;
        $str = '{"time_used":872,"faces":[{"attributes":{"emotion":{"sadness":0.83,"neutral":88.579,"disgust":0.062,"anger":0.062,"surprise":0.457,"fear":0.062,"happiness":9.95},"headpose":{"yaw_angle":-22.609108,"pitch_angle":13.74374,"roll_angle":7.5069175},"blur":{"blurness":{"threshold":50,"value":63.909},"motionblur":{"threshold":50,"value":63.909},"gaussianblur":{"threshold":50,"value":63.909}},"gender":{"value":"Female"},"age":{"value":20},"facequality":{"threshold":70.1,"value":0.006},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":234,"top":1521,"left":1613,"height":234},"face_token":"1e85bfd1dce7c4aa6fa1e84cc269429b"},{"attributes":{"emotion":{"sadness":0.922,"neutral":51.629,"disgust":12.484,"anger":0.307,"surprise":30.639,"fear":0.778,"happiness":3.242},"headpose":{"yaw_angle":17.043566,"pitch_angle":-1.7745702,"roll_angle":-4.5071387},"blur":{"blurness":{"threshold":50,"value":0.126},"motionblur":{"threshold":50,"value":0.126},"gaussianblur":{"threshold":50,"value":0.126}},"gender":{"value":"Male"},"age":{"value":11},"facequality":{"threshold":70.1,"value":42.069},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":210,"top":858,"left":1545,"height":210},"face_token":"3fe5e0f8744dd4d563337df6b370fae6"},{"attributes":{"emotion":{"sadness":53.183,"neutral":6.823,"disgust":0.169,"anger":0.098,"surprise":7.368,"fear":32.26,"happiness":0.098},"headpose":{"yaw_angle":9.456984,"pitch_angle":7.001759,"roll_angle":1.8196449},"blur":{"blurness":{"threshold":50,"value":0.848},"motionblur":{"threshold":50,"value":0.848},"gaussianblur":{"threshold":50,"value":0.848}},"gender":{"value":"Male"},"age":{"value":8},"facequality":{"threshold":70.1,"value":79.817},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":204,"top":239,"left":1584,"height":204},"face_token":"0b43000e01fc02fd3328d86e3adc5682"},{"attributes":{"emotion":{"sadness":0.003,"neutral":0.031,"disgust":0.408,"anger":0.242,"surprise":0.003,"fear":0.003,"happiness":99.311},"headpose":{"yaw_angle":10.561566,"pitch_angle":-0.41406482,"roll_angle":-0.85626817},"blur":{"blurness":{"threshold":50,"value":38.11},"motionblur":{"threshold":50,"value":38.11},"gaussianblur":{"threshold":50,"value":38.11}},"gender":{"value":"Female"},"age":{"value":25},"facequality":{"threshold":70.1,"value":28.169},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":182,"top":157,"left":684,"height":182},"face_token":"ce33fc0d88f1005a1cc54cfb966e98de"}],"image_id":"\/QNDOUxMkqI4FVg8Gvv7yw==","request_id":"1567060832,aab9219e-1902-46da-ba1e-550f90151e97","face_num":4}';
        $arr = json_decode($str,true);
        var_dump($arr['faces'][0]['attributes']);

        foreach($arr['faces'][0] as $k=>$v){
            var_dump($k);
        }
        echo json_encode($arr['faces'][0]['attributes']);
        exit;
        $storeId = 1;
        $startTime = '2019-08-26 12:00:00';
        $endTime = '2019-08-27 12:00:00';
        $storeIdCondition = ' and store_id='.$storeId.' ';//店铺条件
        $updatedAtCondition = " and (updated_at >= '{$startTime}' and updated_at <= '{$endTime}') ";//时间条件
        $baseGrandTotalCondition = " and base_grand_total >= 30 ";//未支付金额超过多少

        $sql = "SELECT customer_id,uid,base_grand_total,store_id FROM soufeel_en.sales_flat_quote
 WHERE is_active=1 and uid !='' {$updatedAtCondition} {$storeIdCondition} {$baseGrandTotalCondition}";
        var_dump($sql);
        /*  array(7) {
  ["faceset_token"]=>
  string(32) "827be7099bd1ece3bfcbffe73b611903"
  ["time_used"]=>
  int(206)
  ["face_count"]=>
  int(0)
  ["face_added"]=>
  int(0)
  ["request_id"]=>
  string(47) "1567068477,d7a7c743-bdf2-4ce0-80ec-e4e15a89f19f"
  ["outer_id"]=>
  string(0) ""
  ["failure_detail"]=>
  array(0) {
  }
}


        array(7) {
  ["faceset_token"]=>
  string(32) "827be7099bd1ece3bfcbffe73b611903"
  ["time_used"]=>
  int(1188)
  ["face_count"]=>
  int(1)
  ["face_added"]=>
  int(1)
  ["request_id"]=>
  string(47) "1567069328,d0deb7ae-ac70-4fda-90ce-7ce3a0d79416"
  ["outer_id"]=>
  string(0) ""
  ["failure_detail"]=>
  array(0) {
  }
}


  */
    }



    //测试异常时，队列是否停止消费
    public function testExceptionConsumerAction(){
        while(true){
            try{
                //test
                $consumerConnInfo = $this->app->core->config->rabbitmq->local->toArray();

                $callback = function ($msg) {
//    echo ' [x] Received ', $msg->body, "\n";
                    try{
                        echo 'received'."\n";
                        var_dump($msg->body);
                        echo " [x] Done\n";
                        $image = 'https://www.baidu666.com';
                        $imageBlobOriginal = $this->app->core->api->Image()->imageFileGetContents($image);
                        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
//                        $image = 'https://www.baidu666.com';
//                        file_get_contents($image);
                    }catch (\Exception $e){
                        var_dump($e->getMessage());
                    }
                    $this->errorMessage();
                };

                $this->app->core->api->RabbitmqConsumer()->consumer($consumerConnInfo,$this->consumerExchangeInfo,$callback,$this->consumerTag,AMQPExchangeType::TOPIC);
            }catch (\Exception $e){
//            var_dump($e->getMessage());
                $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                $this->app->core->api->Log()->writeLog($errorMessage,'Have exception',$this->errorLog,$this->errorFunc);
            }catch (\Error $e){
                $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                $this->app->core->api->Log()->writeLog($errorMessage,'Have error',$this->errorLog,$this->errorFunc);
            }
        }
    }
    public function _consumerException(){
        $callback = function ($message){
            try{
                if(!empty($message->body)){
                    var_dump($message->body);
                    var_dump(__DIR__);
                }
                $image = 'https://www.baidu666.com';
                $imageBlobOriginal = $this->app->core->api->Image()->imageFileGetContents($image);
                $this->errorMessage();
            }catch (\Exception $e){
//                var_dump($e->getMessage());
                $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                $this->app->core->api->Log()->writeLog($errorMessage,'consumer have exception',$this->errorLog,$this->errorFunc);
            }catch (\Error $e){
                $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                $this->app->core->api->Log()->writeLog($errorMessage,'consumer have error',$this->errorLog,$this->errorFunc);
            }
        };
        return $callback;
    }
    public function errorMessage(){
        $errorInfo = error_get_last();
        if(!empty($errorInfo)){
            $errorMessage = "error_type:{$errorInfo['type']},error_message:{$errorInfo['message']},error_file:{$errorInfo['file']},error_line:{$errorInfo['line']}!";
            $this->app->core->api->Log()->writeLog('',$errorMessage,$this->normalErrorLog,$this->errorFunc);
            //restart
            var_dump('restart---------------------------------');
        }
    }
    public function testMergeFaceAction(){
        //测试保存至数据库
        /*
        $data = '{"data":{"id":"2933f4a9573edce2ff26c9d16c78a019","phase":3,"background_url":"","input_url":"http:\/\/qiniu.wanjunjiaoyu.com\/erp\/compress\/20190918\/image_qyt201909041126091568798647.jpg","user_id":"80e6736b04daecc0adbe0438ac33a8a9","emid":"BH450_S02","create_time":"2019-09-24 15:39:25","source":"apis","taskid":"2933f4a9573edce2ff26c9d16c78a019"},"create":false,"input_url":"http:\/\/qiniu.wanjunjiaoyu.com\/erp\/compress\/20190918\/image_qyt201909041126091568798647.jpg","emid":"BH450_S02","source":""}';
        $data = json_decode($data,true);
        $this->app->nwdn->api->Helper()->logFaceTask($data);
        exit;
        $tmpTask = '{"taskid":"test2c38ae38a5cc5eb98aec2848a35f","phase":0,"input_url":"http:\/\/soufeel.mulan.myuxc.com\/custom_product_photos\/original\/20190905\/20190905023106R0TR9v-SCQB13-1.png-soufeel_super_image_ai","source":"consumer_erp_rmq","create_time":"2019-09-05 11:20:08","queue_wait_num":0,"balance":9631,"create":true,"img_link":"http:\/\/soufeel.mulan.myuxc.com\/custom_product_photos\/original\/20190905\/20190905023106R0TR9v-SCQB13-1.png-soufeel_super_image_ai","img_md5":"34c5fb769c05ead0ea1099537711d143"}';
        $createTaskRet = json_decode($tmpTask,true);
        $this->app->nwdn->api->Helper()->logTask($createTaskRet);
        exit;
        */
        //人脸识别
        $image = 'D:\image\init_black\init_black0924\Q190902141810026.png';
        $image2 ='D:\image\init_black\init_black0924\Q190901061510024.png';
        $image3 = 'D:\image\image_wxsyb\timg.jpg';
        $image4 = 'D:\image\image_wxsyb\init_test111.jpg';
        $image5 = 'D:\image\image_wxsyb\timg_ditou.jpg';
        $image6 = 'D:\image\ixiangzuo_20190925111844.jpg';
        $image7 = 'D:\image\ixiangzuo2_20190925111844.jpg';
        $image8 = 'D:\image\ixiangyou2_20190925111844.jpg';
        $base64 = $this->app->core->api->Image()->getBase64ByImageUrl($image8);
        $data = $this->app->face->api->Helper()->faceDetect([ 'image_base64'=> $base64]);
        $this->app->core->api->Log()->writeLog($data['faces'],$image8.' faceDetect ',$this->accessLog,$this->accessFunc);
        exit;

        $imageDir = "D:\image\init_test_angle";
        $datas = scandir($imageDir);
        $i = 0;
        foreach ($datas as $v){
            if($v !="."  && $v !=".." ){
                $i++;
                var_dump($i);
                $image = $imageDir.DIRECTORY_SEPARATOR.$v;
                $base64 = $this->app->core->api->Image()->getBase64ByImageUrl($image);
                $data = $this->app->face->api->Helper()->faceDetect([ 'image_base64'=> $base64]);
                $this->app->core->api->Log()->writeLog($data['faces'],$image.' faceDetect ',$this->accessLog,$this->accessFunc);
            }
        }

        exit;
        
        $sku = 'test';
        $imageDir = 'D:\image\image_0904_niren';
        $imageDir = 'D:\image\image_wxsyb';
        $datas = scandir($imageDir);
        foreach ($datas as $v) {
            if ($v != "." && $v != ".." && $v=='image_sdd20190904114143.jpg' ) {
                try{
                    var_dump($v);
                    $tmpdata = pathinfo($v);
                    $filename = $tmpdata['filename'];
                    if(strpos($filename,'merge') != false){

                    }else {
                        $image = $imageDir . DIRECTORY_SEPARATOR . $v;
                        //本地图片需要压缩
//                $imageBase64 = $this->app->core->api->Image()->getBase64ByImageUrl($image);
                        $imageBlobOriginal = $this->app->core->api->Image()->getBlobByImageUrl($image);
                        $limitSize = $this->app->core->api->Image()->sizeTrillion;
                        $size = 2 * $limitSize;
                        $compressRet = $this->app->core->api->Image()->compressImage($imageBlobOriginal, $size, 2000, 2000);
                        if (empty($compressRet['image_base64str'])) {
                            var_dump('compress error');
                            continue;
                        }
                        $params = [
                            'sku' => $sku,
                            'image_base64' => $compressRet['image_base64str'],
//                            'template_rectangle' => '622,1316,378,378',
//                            'template_rectangle' => '414,876,252,252',
//                            'merge_rectangle'=>'76,228,106,106',
                        ];
                        $data = $this->app->face->api->Helper()->mergeFacePro($params);
                        $imageOut = $imageDir . DIRECTORY_SEPARATOR . $filename . '_merge_without.jpg';
                        $this->app->core->api->Image()->saveBase64($data['base64str'], $imageOut);
                    }
                }catch (\Exception $e){
                    $errorMessage = "error_code:{$e->getCode()},error_message:{$e->getMessage()},error_file:{$e->getFile()},error_line:{$e->getLine()}!";
                    var_dump($errorMessage);
                }
            }
        }


        exit;
        $params = [];
        $temp = "D:\QQDownloads\init_test_face\isingle\i934.png";
        $merge =  "D:\QQDownloads\init_test_face\isingle\i37.png";
        $params = [
            'template_base64' => $this->app->core->api->Image()->getBase64ByImageUrl($temp),
            'merge_base64' => $this->app->core->api->Image()->getBase64ByImageUrl($merge),
        ];
        $data = $this->app->face->api->Helper()->mergeFace($params);
        $base64 = $data['result'] ?? '';
        $filename = 'D:\QQDownloads\init_test_face\isingle\i934_37.png';
        $this->app->core->api->Image()->saveBase64($base64,$filename);
        exit;

    }

    public function testExceptionAction(){
        $imageUrl = 'D:\image\image_wxsyb\image_20190904111134.jpg';
        $image = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
        $data = $this->app->face->api->Helper()->getFace([ 'image_base64'=> $image]);
        foreach ($data as $k=>$v){
            var_dump($k);
        }
        $this->app->core->api->Image()->saveBase64($data['body_image'],'D:\image\image_wxsyb\image_20190904111134_get.jpg');
        var_dump('done');
        exit;

        $data  = $this->app->core->api->Image()->imageFileGetContents('https://spic.qn.cdn.imaiyuan.com/custom_product_photos/original/20190912/20190912231207a29LMw-CQB30-1.png-soufeel_super_image_ai');
        if(!empty($data)){
            var_dump(   'success');
        }else{
            var_dump(   'failed');
        }
        exit;
        $url = 'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190904/20190904211513beTou6-SCQB06Z-1.png-soufeel_super_image_ai_info';
        $data = pathinfo($url);
        var_dump($data);
        $data = parse_url($url);
        var_dump($data);
        exit;
        $imageUrl = 'D:\QQDownloads\init_test_face\init_1036\111.png';
        $image = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
        error_log($image,3,'D:\QQDownloads\init_test_face\init_1036\test.log');
        exit;
        $imageUrl = 'http://soufeel.mulan.myuxc.com/custom_product_photos/original/20190827/20190827000548xTVTC2-CQB08-1.png-soufeel_super_image_ai';
        $image = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
        $data = $this->app->face->api->Helper()->faceDetect([ 'image_base64'=> $image]);
        var_dump($data);
        $fuzzy = $this->app->face->api->Helper()->isBlur($data);
        $data = [
            'fuzzy' => $fuzzy,
            'msg' => ''
        ];
        var_dump($data);
        exit;
        while (true){
            var_dump('start');
            throw new \Exception("throw exception ",1);
            var_dump('end');
        }
        var_dump('while end');
    }

    public function getDetectDataAction(){
        echo "\n";
        for($i=3;$i<28;$i++){
            echo $i.'01'."\n";
            echo $i.'02'."\n";
            echo $i.'03'."\n";
        }
        echo "\n";
        exit;
        $params = [
//            'Key' => '12345',
            'Key' => $this->app->xiangxin->config->face->apiKey,
            'params' => 'test',
        ];
        $faceResult = $this->app->xiangxin->api->Helper()->getToken($params);
        exit;

        $a = "2019-09-09 19:38:37";
        $b = "2019-09-09 20:38:37";
        if($a>$b){
            var_dump($a);
        }else{
            var_dump($b);
        }
        exit;
        $faceResult = $this->app->xiangxin->api->Helper()->getTokn();
        var_dump($faceResult);
        exit;

        $data = '{"base_uri":"http:\/\/120.76.221.184:8087\/","app_id":"SERPCP190805","sign":"3EAA78580D2C6B5B0C8E0877FE0EC0F4","method":"sync_uc_clarity","params":"{\"X190910000010017\":\"https:\\\/\\\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\\\/seekings2\\\/f97708c856c6b4fd802d844aa42976b1.jpg\",\"clarity_type\":1}"}';
        $postData = json_decode($data,true);
        $erpRet = $this->app->core->api->Erp()->notice($postData);
        var_dump($erpRet);
        $data = '{"base_uri":"http:\/\/120.76.221.184:8087\/","app_id":"SERPCP190805","sign":"3EAA78580D2C6B5B0C8E0877FE0EC0F4","method":"sync_uc_clarity","params":"{\"X190910045010058\":\"https:\\\/\\\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\\\/seekings2\\\/46de6ba82c6cbde61c80715f91b4bce0.jpg\",\"clarity_type\":1}"}';
        $postData = json_decode($data,true);
        $erpRet = $this->app->core->api->Erp()->notice($postData);
        var_dump($erpRet);
        $data = '{"base_uri":"http:\/\/120.76.221.184:8087\/","app_id":"SERPCP190805","sign":"3EAA78580D2C6B5B0C8E0877FE0EC0F4","method":"sync_uc_clarity","params":"{\"X190910050010001\":\"https:\\\/\\\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\\\/seekings2\\\/a8abc02e807ebc41f49dec8f54d27ac7.jpg\",\"clarity_type\":1}"}';
        $postData = json_decode($data,true);
        $erpRet = $this->app->core->api->Erp()->notice($postData);
        var_dump($erpRet);
        $data = '{"base_uri":"http:\/\/120.76.221.184:8087\/","app_id":"SERPCP190805","sign":"3EAA78580D2C6B5B0C8E0877FE0EC0F4","method":"sync_uc_clarity","params":"{\"X190910060010016\":\"https:\\\/\\\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\\\/seekings2\\\/df282c48e6de7ec55ab46e20aa16f40d.jpg\",\"clarity_type\":1}"}';
        $postData = json_decode($data,true);
        $erpRet = $this->app->core->api->Erp()->notice($postData);
        var_dump($erpRet);
        $data = '{"base_uri":"http:\/\/120.76.221.184:8087\/","app_id":"SERPCP190805","sign":"3EAA78580D2C6B5B0C8E0877FE0EC0F4","method":"sync_uc_clarity","params":"{\"X190910075010037\":\"https:\\\/\\\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\\\/seekings2\\\/ae6f71ae7df044d8d2ede7f2cee36567.jpg\",\"clarity_type\":1}"}';
        $postData = json_decode($data,true);
        $erpRet = $this->app->core->api->Erp()->notice($postData);
        var_dump($erpRet);
        $data = '{"base_uri":"http:\/\/120.76.221.184:8087\/","app_id":"SERPCP190805","sign":"3EAA78580D2C6B5B0C8E0877FE0EC0F4","method":"sync_uc_clarity","params":"{\"X190910065010060\":\"https:\\\/\\\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\\\/seekings2\\\/f611ff2bb2c51d0da290ddfb9cee4b33.jpg\",\"clarity_type\":1}"}';
        $postData = json_decode($data,true);
        $erpRet = $this->app->core->api->Erp()->notice($postData);
        var_dump($erpRet);
        $data = '{"base_uri":"http:\/\/120.76.221.184:8087\/","app_id":"SERPCP190805","sign":"3EAA78580D2C6B5B0C8E0877FE0EC0F4","method":"sync_uc_clarity","params":"{\"X190910075010040\":\"https:\\\/\\\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\\\/seekings2\\\/570159fbf62aeb489d58e34de18e1884.jpg\",\"clarity_type\":1}"}';
        $postData = json_decode($data,true);
        $erpRet = $this->app->core->api->Erp()->notice($postData);
        var_dump($erpRet);
        $data = '{"base_uri":"http:\/\/120.76.221.184:8087\/","app_id":"SERPCP190805","sign":"3EAA78580D2C6B5B0C8E0877FE0EC0F4","method":"sync_uc_clarity","params":"{\"X190910090010001\":\"https:\\\/\\\/nwdn-hd2.oss-cn-shanghai.aliyuncs.com\\\/seekings2\\\/65545ee61dd8523210e1c184028c642e.jpg\",\"clarity_type\":1}"}';
        $postData = json_decode($data,true);
        $erpRet = $this->app->core->api->Erp()->notice($postData);
        var_dump($erpRet);
        exit;

        $data = '{"time_used":598,"faces":[{"attributes":{"emotion":{"sadness":89.911,"neutral":0.141,"disgust":0.076,"anger":0.076,"surprise":5.574,"fear":0.297,"happiness":3.925},"headpose":{"yaw_angle":-32.689034,"pitch_angle":19.308859,"roll_angle":0.7292414},"blur":{"blurness":{"threshold":50,"value":2.135},"motionblur":{"threshold":50,"value":2.135},"gaussianblur":{"threshold":50,"value":2.135}},"gender":{"value":"Male"},"age":{"value":6},"facequality":{"threshold":70.1,"value":0.006},"ethnicity":{"value":"WHITE"}},"face_rectangle":{"width":90,"top":748,"left":1084,"height":90},"face_token":"a1e246f4d106afbf86a7bbd75c10d159"}],"image_id":"1qZIwhBDGmkIdLiQLtD+GA==","request_id":"1568095824,e2b7377f-c01a-4071-a469-3d94d6a6c6ea","face_num":1}';
        $faceResult = json_decode($data,true);
        $canCreateTask = $this->app->face->api->Helper()->isBlur($faceResult);
        var_dump($canCreateTask);
        exit;
        $data = '{"base_uri":"http:\/\/120.76.221.184:8087\/","app_id":"SERPCP190805","sign":"3EAA78580D2C6B5B0C8E0877FE0EC0F4","method":"sync_uc_clarity","params":"{\"X190910095010037\":\"https:\\\/\\\/spic.qn.cdn.imaiyuan.com\\\/custom_product_photos\\\/original\\\/20190910\\\/20190910094159VXkyYw-CQB02-1.png\",\"clarity_type\":0}"}';
        $data = '{"base_uri":"http:\/\/120.76.221.184:8087\/","app_id":"SERPCP190805","sign":"3EAA78580D2C6B5B0C8E0877FE0EC0F4","method":"sync_uc_clarity","params":"{\"X190910095010037\":\"https:\\\/\\\/spic.qn.cdn.imaiyuan.com\\\/custom_product_photos\\\/original\\\/20190910\\\/20190910094159VXkyYw-CQB02-1.png\",\"clarity_type\":0}"}';
        $postData = json_decode($data,true);
        $erpRet = $this->app->core->api->Erp()->notice($postData);
        var_dump($erpRet);
        exit;

        $base64str = $this->app->core->api->Image()->getBase64ByImageUrl('https://nwdn-hd2.oss-cn-shanghai.aliyuncs.com/seekings2/cea0905594091344d04733677b8a2366.jpg');
        $faceImageParams = [
//            'image_url' => $imgLink,//因facepp-api访问图片地址时会出现超时
            'image_base64' => $base64str,
        ];
        $faceResult = $this->app->face->api->Helper()->faceDetect($faceImageParams,'consumer_erp');

        exit;
        $datas = '{"time_used":676,"faces":[{"attributes":{"emotion":{"sadness":0,"neutral":0.01,"disgust":0,"anger":0.001,"surprise":2.382,"fear":0,"happiness":97.605},"headpose":{"yaw_angle":-6.3993177,"pitch_angle":-9.815194,"roll_angle":24.048332},"blur":{"blurness":{"threshold":50,"value":0.083},"motionblur":{"threshold":50,"value":0.083},"gaussianblur":{"threshold":50,"value":0.083}},"gender":{"value":"Male"},"age":{"value":24},"facequality":{"threshold":70.1,"value":85.224},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":394,"top":909,"left":435,"height":394},"face_token":"7aea8d4dd93bb5d3690a2ebd6361f3e8"},{"attributes":{"emotion":{"sadness":0.005,"neutral":51.134,"disgust":0.017,"anger":0.006,"surprise":0.127,"fear":0.005,"happiness":48.705},"headpose":{"yaw_angle":-5.13054,"pitch_angle":-6.888156,"roll_angle":3.5573566},"blur":{"blurness":{"threshold":50,"value":50.412},"motionblur":{"threshold":50,"value":50.412},"gaussianblur":{"threshold":50,"value":50.412}},"gender":{"value":"Female"},"age":{"value":33},"facequality":{"threshold":70.1,"value":0.006},"ethnicity":{"value":"WHITE"}},"face_rectangle":{"width":252,"top":572,"left":849,"height":252},"face_token":"4c19343252385773f5886fc62c2e84f7"},{"attributes":{"emotion":{"sadness":1.026,"neutral":0.343,"disgust":95.62,"anger":2.714,"surprise":0.202,"fear":0.048,"happiness":0.048},"headpose":{"yaw_angle":-7.6822596,"pitch_angle":-23.521988,"roll_angle":8.49194},"blur":{"blurness":{"threshold":50,"value":0.379},"motionblur":{"threshold":50,"value":0.379},"gaussianblur":{"threshold":50,"value":0.379}},"gender":{"value":"Male"},"age":{"value":45},"facequality":{"threshold":70.1,"value":42.274},"ethnicity":{"value":"INDIA"}},"face_rectangle":{"width":211,"top":230,"left":750,"height":211},"face_token":"a5ce5c5da9bfbbbfbe44fa8d8347beed"},{"attributes":{"emotion":{"sadness":0.014,"neutral":90.5,"disgust":0.008,"anger":0.023,"surprise":1.37,"fear":0.022,"happiness":8.063},"headpose":{"yaw_angle":9.112277,"pitch_angle":-37.276875,"roll_angle":-5.4445124},"blur":{"blurness":{"threshold":50,"value":3.003},"motionblur":{"threshold":50,"value":3.003},"gaussianblur":{"threshold":50,"value":3.003}},"gender":{"value":"Female"},"age":{"value":52},"facequality":{"threshold":70.1,"value":0.006},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":210,"top":405,"left":1163,"height":210},"face_token":"c397c8854624d0e04109d6fa22aee588"}],"image_id":"oesSxykBU80VbICpIPQtTA==","request_id":"1567672976,cc96e264-7cf3-4a07-88f0-5d1a909424df","face_num":4}';
        $datas = json_decode($datas,true);
        $data = $this->app->face->api->Helper()->logDetectUpdate(1,$datas);
        exit;

        $data = '{"time_used":676,"faces":[{"attributes":{"emotion":{"sadness":0,"neutral":0.01,"disgust":0,"anger":0.001,"surprise":2.382,"fear":0,"happiness":97.605},"headpose":{"yaw_angle":-6.3993177,"pitch_angle":-9.815194,"roll_angle":24.048332},"blur":{"blurness":{"threshold":50,"value":0.083},"motionblur":{"threshold":50,"value":0.083},"gaussianblur":{"threshold":50,"value":0.083}},"gender":{"value":"Male"},"age":{"value":24},"facequality":{"threshold":70.1,"value":85.224},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":394,"top":909,"left":435,"height":394},"face_token":"7aea8d4dd93bb5d3690a2ebd6361f3e8"},{"attributes":{"emotion":{"sadness":0.005,"neutral":51.134,"disgust":0.017,"anger":0.006,"surprise":0.127,"fear":0.005,"happiness":48.705},"headpose":{"yaw_angle":-5.13054,"pitch_angle":-6.888156,"roll_angle":3.5573566},"blur":{"blurness":{"threshold":50,"value":50.412},"motionblur":{"threshold":50,"value":50.412},"gaussianblur":{"threshold":50,"value":50.412}},"gender":{"value":"Female"},"age":{"value":33},"facequality":{"threshold":70.1,"value":0.006},"ethnicity":{"value":"WHITE"}},"face_rectangle":{"width":252,"top":572,"left":849,"height":252},"face_token":"4c19343252385773f5886fc62c2e84f7"},{"attributes":{"emotion":{"sadness":1.026,"neutral":0.343,"disgust":95.62,"anger":2.714,"surprise":0.202,"fear":0.048,"happiness":0.048},"headpose":{"yaw_angle":-7.6822596,"pitch_angle":-23.521988,"roll_angle":8.49194},"blur":{"blurness":{"threshold":50,"value":0.379},"motionblur":{"threshold":50,"value":0.379},"gaussianblur":{"threshold":50,"value":0.379}},"gender":{"value":"Male"},"age":{"value":45},"facequality":{"threshold":70.1,"value":42.274},"ethnicity":{"value":"INDIA"}},"face_rectangle":{"width":211,"top":230,"left":750,"height":211},"face_token":"a5ce5c5da9bfbbbfbe44fa8d8347beed"},{"attributes":{"emotion":{"sadness":0.014,"neutral":90.5,"disgust":0.008,"anger":0.023,"surprise":1.37,"fear":0.022,"happiness":8.063},"headpose":{"yaw_angle":9.112277,"pitch_angle":-37.276875,"roll_angle":-5.4445124},"blur":{"blurness":{"threshold":50,"value":3.003},"motionblur":{"threshold":50,"value":3.003},"gaussianblur":{"threshold":50,"value":3.003}},"gender":{"value":"Female"},"age":{"value":52},"facequality":{"threshold":70.1,"value":0.006},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":210,"top":405,"left":1163,"height":210},"face_token":"c397c8854624d0e04109d6fa22aee588"}],"image_id":"oesSxykBU80VbICpIPQtTA==","request_id":"1567672976,cc96e264-7cf3-4a07-88f0-5d1a909424df","face_num":4}';
//        $data = '[{"attributes":{"emotion":{"sadness":0,"neutral":0.01,"disgust":0,"anger":0.001,"surprise":2.382,"fear":0,"happiness":97.605},"headpose":{"yaw_angle":-6.3993177,"pitch_angle":-9.815194,"roll_angle":24.048332},"blur":{"blurness":{"threshold":50,"value":0.083},"motionblur":{"threshold":50,"value":0.083},"gaussianblur":{"threshold":50,"value":0.083}},"gender":{"value":"Male"},"age":{"value":24},"facequality":{"threshold":70.1,"value":85.224},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":394,"top":909,"left":435,"height":394},"face_token":"7aea8d4dd93bb5d3690a2ebd6361f3e8"},{"attributes":{"emotion":{"sadness":0.005,"neutral":51.134,"disgust":0.017,"anger":0.006,"surprise":0.127,"fear":0.005,"happiness":48.705},"headpose":{"yaw_angle":-5.13054,"pitch_angle":-6.888156,"roll_angle":3.5573566},"blur":{"blurness":{"threshold":50,"value":50.412},"motionblur":{"threshold":50,"value":50.412},"gaussianblur":{"threshold":50,"value":50.412}},"gender":{"value":"Female"},"age":{"value":33},"facequality":{"threshold":70.1,"value":0.006},"ethnicity":{"value":"WHITE"}},"face_rectangle":{"width":252,"top":572,"left":849,"height":252},"face_token":"4c19343252385773f5886fc62c2e84f7"},{"attributes":{"emotion":{"sadness":1.026,"neutral":0.343,"disgust":95.62,"anger":2.714,"surprise":0.202,"fear":0.048,"happiness":0.048},"headpose":{"yaw_angle":-7.6822596,"pitch_angle":-23.521988,"roll_angle":8.49194},"blur":{"blurness":{"threshold":50,"value":0.379},"motionblur":{"threshold":50,"value":0.379},"gaussianblur":{"threshold":50,"value":0.379}},"gender":{"value":"Male"},"age":{"value":45},"facequality":{"threshold":70.1,"value":42.274},"ethnicity":{"value":"INDIA"}},"face_rectangle":{"width":211,"top":230,"left":750,"height":211},"face_token":"a5ce5c5da9bfbbbfbe44fa8d8347beed"},{"attributes":{"emotion":{"sadness":0.014,"neutral":90.5,"disgust":0.008,"anger":0.023,"surprise":1.37,"fear":0.022,"happiness":8.063},"headpose":{"yaw_angle":9.112277,"pitch_angle":-37.276875,"roll_angle":-5.4445124},"blur":{"blurness":{"threshold":50,"value":3.003},"motionblur":{"threshold":50,"value":3.003},"gaussianblur":{"threshold":50,"value":3.003}},"gender":{"value":"Female"},"age":{"value":52},"facequality":{"threshold":70.1,"value":0.006},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":210,"top":405,"left":1163,"height":210},"face_token":"c397c8854624d0e04109d6fa22aee588"}]';
        $data = json_decode($data,true);
//        var_dump($v);exit;
        $imagesId = 1;
//        $data['face_num'] = '';
        foreach ($data as $k=>$v){
//            var_dump($k);//
            if($k=='faces'){
//                var_dump($v);
                foreach ($v as $kv=>$vv){
//                    var_dump($kv); //0 1 2 3
                    var_dump($vv['attributes']);
                    foreach ($vv as $kvv=>$vvv){
//                        var_dump($kvv);
//                        string(10) "attributes"
//string(14) "face_rectangle"
//string(10) "face_token"
                    }
                }

            }

        }
        exit;
        foreach ($v as $kv=>$vv){
            if($kv=='faces'){
                var_dump($vv['attributes']);
            }
            continue;
            $insertData = [
                'images_id' => $imagesId,
                //attributes-gender value Female Male
                'gender' => isset($vv['attributes']['gender']['value']) ? strtolower($vv['attributes']['gender']['value'])=='female'?1:0 : 0,
                'age' => $vv['attributes']['age']['value'] ?? 0,
                //anger：愤怒,disgust：厌恶,fear：恐惧,happiness：高兴,neutral：平静,sadness：伤心,surprise：惊讶
                'emotion' => isset($vv['attributes']['emotion']) ? $this->getEmotion($vv['attributes']['emotion']) : '',
                //Asian亚洲人,White白人,Black黑人
                'ethnicity' => $vv['attributes']['ethnicity']['value'] ?? '',
                'facequality' => $vv['attributes']['facequality']['value'] ?? 0,
                //attributes-blur blurness motionblur gaussianblur
                'blur' => isset($vv['attributes']['blur']) ? $this->getSingleBlur($vv['attributes']['blur']) : -1,
                //attributes-headpose 3个 抬头 旋转（平面旋转）摇头
                'headpose_pitch_angle' => $vv['attributes']['headpose']['pitch_angle'] ?? '',
                'headpose_roll_angle' => $vv['attributes']['headpose']['roll_angle'] ?? '',
                'headpose_yaw_angle' => $vv['attributes']['headpose']['yaw_angle'] ?? '',
                'face_rectangle_top' => $vv['face_rectangle']['top'] ?? 0,
                'face_rectangle_left' => $vv['face_rectangle']['left'] ?? 0,
                'face_rectangle_width' => $vv['face_rectangle']['width'] ?? 0,
                'face_rectangle_height' => $vv['face_rectangle']['height'] ?? 0,
                'init_face_data' => $data,
                'face_token' => $vv['face_token'] ?? '',
            ];
            $insertData['face_rectangle'] = $insertData['face_rectangle_top'].','.$insertData['face_rectangle_left'].','.$insertData['face_rectangle_width'].','.$insertData['face_rectangle_height'];
//            var_dump($insertData);
        }

        exit;
        $data = $this->app->face->api->Helper()->logDetectCreate([ 'image_base64'=> 'abc']);
        var_dump($data);
        exit;
        $datas = '{"time_used":676,"faces":[{"attributes":{"emotion":{"sadness":0,"neutral":0.01,"disgust":0,"anger":0.001,"surprise":2.382,"fear":0,"happiness":97.605},"headpose":{"yaw_angle":-6.3993177,"pitch_angle":-9.815194,"roll_angle":24.048332},"blur":{"blurness":{"threshold":50,"value":0.083},"motionblur":{"threshold":50,"value":0.083},"gaussianblur":{"threshold":50,"value":0.083}},"gender":{"value":"Male"},"age":{"value":24},"facequality":{"threshold":70.1,"value":85.224},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":394,"top":909,"left":435,"height":394},"face_token":"7aea8d4dd93bb5d3690a2ebd6361f3e8"},{"attributes":{"emotion":{"sadness":0.005,"neutral":51.134,"disgust":0.017,"anger":0.006,"surprise":0.127,"fear":0.005,"happiness":48.705},"headpose":{"yaw_angle":-5.13054,"pitch_angle":-6.888156,"roll_angle":3.5573566},"blur":{"blurness":{"threshold":50,"value":50.412},"motionblur":{"threshold":50,"value":50.412},"gaussianblur":{"threshold":50,"value":50.412}},"gender":{"value":"Female"},"age":{"value":33},"facequality":{"threshold":70.1,"value":0.006},"ethnicity":{"value":"WHITE"}},"face_rectangle":{"width":252,"top":572,"left":849,"height":252},"face_token":"4c19343252385773f5886fc62c2e84f7"},{"attributes":{"emotion":{"sadness":1.026,"neutral":0.343,"disgust":95.62,"anger":2.714,"surprise":0.202,"fear":0.048,"happiness":0.048},"headpose":{"yaw_angle":-7.6822596,"pitch_angle":-23.521988,"roll_angle":8.49194},"blur":{"blurness":{"threshold":50,"value":0.379},"motionblur":{"threshold":50,"value":0.379},"gaussianblur":{"threshold":50,"value":0.379}},"gender":{"value":"Male"},"age":{"value":45},"facequality":{"threshold":70.1,"value":42.274},"ethnicity":{"value":"INDIA"}},"face_rectangle":{"width":211,"top":230,"left":750,"height":211},"face_token":"a5ce5c5da9bfbbbfbe44fa8d8347beed"},{"attributes":{"emotion":{"sadness":0.014,"neutral":90.5,"disgust":0.008,"anger":0.023,"surprise":1.37,"fear":0.022,"happiness":8.063},"headpose":{"yaw_angle":9.112277,"pitch_angle":-37.276875,"roll_angle":-5.4445124},"blur":{"blurness":{"threshold":50,"value":3.003},"motionblur":{"threshold":50,"value":3.003},"gaussianblur":{"threshold":50,"value":3.003}},"gender":{"value":"Female"},"age":{"value":52},"facequality":{"threshold":70.1,"value":0.006},"ethnicity":{"value":"BLACK"}},"face_rectangle":{"width":210,"top":405,"left":1163,"height":210},"face_token":"c397c8854624d0e04109d6fa22aee588"}],"image_id":"oesSxykBU80VbICpIPQtTA==","request_id":"1567672976,cc96e264-7cf3-4a07-88f0-5d1a909424df","face_num":4}';
        $datas = json_decode($datas,true);
        foreach ($datas as $k=>$v){
            if($k=='faces'){
                $singleFaceData = json_encode($v);
                foreach ($v as $kv=>$vv){
                    var_dump('+++++++++++++++++++++++');
                    //attributes-emotion 多个取最大值
//anger：愤怒,disgust：厌恶,fear：恐惧,happiness：高兴,neutral：平静,sadness：伤心,surprise：惊讶
                    $emotion = $this->getEmotion($vv['attributes']['emotion']);
                    var_dump($vv['attributes']['emotion']);
                    var_dump($emotion);
                    //attributes-gender value Female Male
                    var_dump($vv['attributes']['gender']['value']);
                    //attributes-age value
                    var_dump($vv['attributes']['age']['value']);
                    //attributes-ethnicity value
                    //Asian亚洲人,White白人,Black黑人
                    var_dump($vv['attributes']['ethnicity']['value']);
                    //attributes-headpose 3个 抬头 旋转（平面旋转）摇头
                    var_dump($vv['attributes']['headpose']);
                    //attributes-blur blurness motionblur gaussianblur
                    var_dump($vv['attributes']['blur']);
                    //attributes-facequality
                    var_dump($vv['attributes']['facequality']);
                    //face_rectangle
                    var_dump($vv['face_rectangle']);
                    //face_token
                    var_dump($vv['face_token']);
                    var_dump('-----------------------'."\n");
                }
            }
        }

    }

    private function getEmotion($arr){
        $baseKey = '';
        $baseValue = 0;
        foreach ($arr as $k=>$v){
            if($v>=$baseValue){
                $baseValue = $v;
                $baseKey = $k;
            }
        }
        return $baseKey;
    }
    private function getSingleBlur($blurData){
        //attributes-blur blurness motionblur gaussianblur
        if(isset($blurData['blurness'])){
            if($blurData['blurness']['value'] > $blurData['blurness']['threshold']
                || $blurData['motionblur']['value'] > $blurData['motionblur']['threshold']
                || $blurData['gaussianblur']['value'] > $blurData['gaussianblur']['threshold']){
                return 1;

            }else{
                return 0;
            }

        }else{
            return -1;
        }

    }


    public function testCreateFaceTask(){
        $sku = 'test';
        $ethnicity = 'S0887987';
        $inputUrl = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568777478.jpg';//jzs
        if(empty($sku) || empty($ethnicity) || empty($inputUrl)){
            var_dump('params is error');exit;
        }
        $ethnicity = substr($ethnicity,0,3);
        $configInfo = $this->app->nwdn->config->sku->toArray();
        $backgroundUrl = '';
        $emid = $configInfo[$sku][$ethnicity] ?? '';

        if(empty($emid)){
            var_dump('have emid');exit;
        }
        //创建换脸任务
        $data = $this->app->nwdn->api->Helper()->createFaceTask($inputUrl,$emid);
        $this->app->core->api->Log()->writeLog($data,' createFaceTask end ',$this->accessLog,$this->accessFunc);
        var_dump('task data is++++++++++++++++++++++++++++++++');
        var_dump($data);
//        $data = '{"data":{"id":"7ed2e0c131735271d63ab3d2067f5f64","phase":0,"background_url":"","input_url":"http:\/\/qiniu.wanjunjiaoyu.com\/erp\/compress\/20190918\/image_jzs201909041116131568777478.jpg","user_id":"80e6736b04daecc0adbe0438ac33a8a9","emid":"m01","create_time":"2019-09-20 11:13:35","source":"apis","taskid":"7ed2e0c131735271d63ab3d2067f5f64"},"create":true,"input_url":"http:\/\/qiniu.wanjunjiaoyu.com\/erp\/compress\/20190918\/image_jzs201909041116131568777478.jpg","emid":"m01","source":""}';
//        $data = json_decode($data,true);
        $taskId = $data['data']['taskid'] ?? '';
        var_dump('taskId is++++++++++++++++++++++++++++++++');
        var_dump($taskId);
        if(empty($taskId)){
            var_dump('have no task id');exit;
        }

        $outputUrl = '';
        //根据任务ID查询换脸任务(查询10次，每次间隔0.5秒)
        for($i=0;$i<10;$i++){
            $taskData = $this->app->nwdn->api->Helper()->getFaceTask($taskId);
            var_dump('getTaskData by '.$taskId.' is++++++++++++++++++++++++++++++++'.$i);
            var_dump($taskData);
            if(isset($taskData['data']['output_url']) && !empty($taskData['data']['output_url'])){
                $outputUrl = $taskData['data']['output_url'];
                break;
            }
            usleep(500000);//0.5s
        }
        var_dump($outputUrl);
        exit;

        /*
        $backgroundImage = 'D:\image\image_wxsyb\temp.jpg';
        $pathParts = pathinfo($backgroundImage);
        $imageOriginalName = $pathParts['filename'].time();//获取图片名称
        //上传至七牛
        $imageName = 'erp/compress/'.date('Ymd').'/'.$imageOriginalName.'.jpg';
        $blob = $this->app->core->api->Image()->getBlobByImageUrl($backgroundImage);
        $this->app->core->api->Log()->writeLog('','upload compress image blob to qiniu start',$this->accessLog,$this->accessFunc);
        $qiniuUploadRet = $this->app->admin->core->api->Qiniu()->uploadBlobToQiniu($blob,$imageName);
        $this->app->core->api->Log()->writeLog($qiniuUploadRet,'upload compress image blob to qiniu end with',$this->accessLog,$this->accessFunc);
        if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
            $backgroundUrl = $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url'];
        }else{
            $backgroundUrl = '';
        }
        var_dump($backgroundUrl);
        //        $inputUrl = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_201909040948491568776398.jpg';
        $inputUrls = [
            'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568796456.jpg',
            'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568796458.jpg',
            'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568796459.jpg',
            'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568796459.jpg',
            'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568796460.jpg',
            'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568796461.jpg',
            'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568796461.jpg',
            'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568796462.jpg',
            'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568796463.jpg',
            'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568796463.jpg',
        ];
        */
        $backgroundUrl = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/temp1568776185.jpg';
        /*
//        $inputImage = 'D:\image\image_wxsyb\image_20190904094849.jpg';//liuduo
        $inputImage = 'D:\image\image_wxsyb\image_jzs20190904111613.jpg';//jinzhongshuai
        $inputImage = 'D:\image\image_wxsyb0918\image_20190918163958.jpg';//xintongshi
        $inputImage = 'D:\image\image_wxsyb0918\image_20190904111134.jpg';//dadong
        $inputImage = 'D:\image\image_wxsyb0918\image_lc20190904112058.jpg';//liuchao
        $inputImage = 'D:\image\image_wxsyb0918\image_ld20190904094849.jpg';//liuduo
        $inputImage = 'D:\image\image_wxsyb0918\image_qyt20190904112609.jpg';//qyt
        $pathParts = pathinfo($inputImage);
        $imageOriginalName = $pathParts['filename'].time();//获取图片名称
        //上传至七牛
        $imageName = 'erp/compress/'.date('Ymd').'/'.$imageOriginalName.'.jpg';
        $blob = $this->app->core->api->Image()->getBlobByImageUrl($inputImage);
        $this->app->core->api->Log()->writeLog('','upload compress image blob to qiniu start',$this->accessLog,$this->accessFunc);
        $qiniuUploadRet = $this->app->admin->core->api->Qiniu()->uploadBlobToQiniu($blob,$imageName);
        $this->app->core->api->Log()->writeLog($qiniuUploadRet,'upload compress image blob to qiniu end with',$this->accessLog,$this->accessFunc);
        if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
            $inputUrl = $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url'];
        }else{
            $inputUrl = '';
        }
        echo "'".$inputUrl."'"."\n";

        exit;
        */

        $inputUrl = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_jzs201909041116131568777478.jpg';//jzs
        $inputUrl = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_201909181639581568797626.jpg';//xintongshi
        $inputUrl = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_201909041111341568798002.jpg';//dadong
        $inputUrl = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_lc201909041120581568798201.jpg';//liuchao
        $inputUrl = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_ld201909040948491568798439.jpg';//liuduo
        $inputUrl = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_qyt201909041126091568798647.jpg';//qyt
        $this->app->core->api->Log()->writeLog('','createFaceTask',$this->accessLog,$this->accessFunc);
        $data = $this->app->nwdn->api->Helper()->createFaceTask($inputUrl,$backgroundUrl);
        $this->app->core->api->Log()->writeLog($data,' createFaceTask end ',$this->accessLog,$this->accessFunc);
        if(isset($data['data']['taskid'])){
            error_log('"'.$data['data']['taskid'].'",'."\n",3,'D:\image\image_wxsyb\task_id.log');
        }


    }
    public function getFaceTaskAction(){
        $inputUrl = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_qyt201909041126091568798647.jpg';//qyt
        $data = pathinfo($inputUrl);
        $key = $data['basename'];
        $token = $this->app->admin->core->api->Qiniu()->getTokenByKey($key);
        var_dump($token);
        exit;


        $taskId = 'ef12363aa739ac0c49a0e638027fa55f';//xintongshi
        $taskId = '5d91069c30576fba95551b576564fce8';//jzs
        $taskId = '2c920980469ad27b4a8a30bac1246c58';//dadong
        $taskId = 'd15686290c65157eef870fb35bbdc120';//liuchao
        $taskId = '574a6eaf41998d1afc3318ad10a94325';//liuduo
        $taskId = '41aff8122779909afa07fff52a5a8452';//qyt
        $taskId = '7ed2e0c131735271d63ab3d2067f5f64';
        $taskId = '5a0f76440537187d84ad5a09ef2ead60';
        $data = $this->app->nwdn->api->Helper()->getFaceTask($taskId);
        var_dump($data);
        exit;
        $logUrl = 'D:\image\image_wxsyb\nwdn_face_task.log';
        $logDatas = file_get_contents($logUrl);
        $logDatas = explode("\n",$logDatas);
        foreach ($logDatas as $lv){
            if(!empty($lv)){
                $data = pathinfo($lv);
                $fileName = $data['filename'];
                $this->saveImage($lv,$fileName);
            }
        }
        exit;
//        $taskId = '0ad248a9ba837d71a30f2d032cf5fe04';
//        $taskId = '5d61a9916659ce51815912261b918b0a';
//        $taskId = '5d91069c30576fba95551b576564fce8';//jzs
        $taskIds = [
            "72c498d87731f4089da940e6415c7e0d",
            "f8e518998c4224066a8743d945ae2a36",
            "30ffc2d7ce08d06e708366d7d1b5fa23",
            "0c9cddead54394886bb0fee7b961c49f",
            "c747a569f957aa15cf2ac320a82b3938",
            "67683f9048fe35ecb0a94253212093e7",
            "463651bfda8ae122cd23ebbd8bedf8a7",
            "979aa3935ddc4e80f6c3f52370331824",
            "0022895a9388096aad72cdc1668803cf",
            "48012ee01e2be37583934f0712f2b421",
            "5d91069c30576fba95551b576564fce8",
        ];
        foreach ($taskIds as $taskId){
            $data = $this->app->nwdn->api->Helper()->getFaceTask($taskId);
            if(isset($data['data']['output_url'])){
                error_log($data['data']['output_url']."\n",3,'D:\image\image_wxsyb\nwdn_face_task.log');

            }
        }

        exit;


        $imageUrl = 'https://oss.mtlab.meitu.com/mtopen/rF5GIhp5ReLKgLV91CKj5BO1q2FTLMmc/MTU2ODc3NTYwMA==/ad5c935a-80d1-4060-9ae6-619425a6851a.jpg';
        $base64 = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
        $params = [
            "parameter" => [
                "version"=> "1.0.1",
                'rsp_media_type' => 'url'
            ],
            "media_info_list" => [
                [
                    "media_data" => $base64,
                    "media_profiles" => ["media_data_type" => "jpg"]
                ]
            ],
        ];
        $data = $this->app->meitu->api->Helper()->hdr($params);
        $imageOutUrl =$data['media_info_list'][0]['media_data'];
        var_dump($imageOutUrl);
        exit;





        $data = '{
            "parameter": {
            "version":"1.0.1"
    },
    "extra": {},
    "media_info_list": [{
            "media_data": "...",
        "media_profiles": {
                "media_data_type": "jpg"
        }
    }]

}';
        $d = json_decode($data,true);
        var_dump($d);
        exit;

    }

    public function meituTaskAction(){
        $imageDir = "D:\image\image_hdr";
        $datas = scandir($imageDir);
        $i = 0;
        foreach ($datas as $v){
            if($v !="."  && $v !=".."
                /*&&
                ($v == '20190803131601vdrzz0-GYKKL06-1.png'
                    ||$v=='20190803134732Oc9tot-TC195-1.png'
                    ||$v=='20190803134949a48k4S-TC195-1.png'
                    ||$v=='20190803175927VVx6Z6-SJK132P-1.png'
                    ||$v=='20190803205937LurP93-NNPS605-1.png'
                    ||$v=='20190912231207a29LMw-CQB30-1.png'
                    ||$v=='T190902024010072.png'
                )*/
            ){
                $i++;
                var_dump($i);
                var_dump($v);
                $imageUrl = $imageDir.DIRECTORY_SEPARATOR.$v;
//                $imageUrl = 'https://oss.mtlab.meitu.com/mtopen/rF5GIhp5ReLKgLV91CKj5BO1q2FTLMmc/MTU2ODc3NTYwMA==/ad5c935a-80d1-4060-9ae6-619425a6851a.jpg';
                $base64 = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
                $params = [
                    "parameter" => [
                        "version"=> "1.0.1",
                        'rsp_media_type' => 'url'
                    ],
                    "media_info_list" => [
                        [
                            "media_data" => $base64,
                            "media_profiles" => ["media_data_type" => "jpg"]
                        ]
                    ],
                ];
                $data = $this->app->meitu->api->Helper()->hdr($params);
                $imageOutUrl =$data['media_info_list'][0]['media_data'];
                error_log($imageOutUrl."\n",3,'D:\image\image_hdr_0918'.DIRECTORY_SEPARATOR.$v.'.log');
            }
        }
    }
    public function nwdnTaskAction(){
        $imageUrl = 'D:\image\itest\201908030106456cndmo-SJT243F-1.png';
        $base64str = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
        if(!empty($base64str)){
            $faceImageParams = [
                'image_base64' => $base64str,
            ];

            $this->app->core->api->Log()->writeLog('','get detect by face++ start with',$this->accessLog,$this->accessFunc);
            $faceResult = $this->app->face->api->Helper()->faceDetect($faceImageParams,'consumer_erp');
            var_dump($faceResult);
            $this->app->core->api->Log()->writeLog($faceResult,'get detect by face++ end with',$this->accessLog,$this->accessFunc);
            $canCreateTask = $this->app->face->api->Helper()->isBlur($faceResult);
            var_dump($canCreateTask);
            $this->app->core->api->Log()->writeLog($canCreateTask,'get isblur by face++ end with',$this->accessLog,$this->accessFunc);
        }
        exit;

        $imageDir = "D:\image\image_hdr_0918";
        $datas = scandir($imageDir);
        foreach ($datas as $v){
            if($v !="."  && $v !=".."  && strpos($v,'log')!==false){
                $logUrl = $imageDir.DIRECTORY_SEPARATOR.$v;
                $logDatas = file_get_contents($logUrl);
                $logDatas = explode("\n",$logDatas);
                foreach ($logDatas as $lv){
                    if(!empty($lv)){
                        $data = pathinfo($v);
                        $fileName = pathinfo($data['filename'])['filename'];
                        $this->saveImage($lv,$fileName);
                    }
                }
            }
        }

        exit;

        $imageDir = "D:\image\image_hdr";
        $datas = scandir($imageDir);
        $i = 0;
        foreach ($datas as $v){
            if($v !="."  && $v !=".."  ){
                $i++;
                var_dump($i);
                $imageUrl = $imageDir.DIRECTORY_SEPARATOR.$v;
                $pathParts = pathinfo($imageUrl);
                $imageOriginalName = $pathParts['filename'].time();//获取图片名称
                //上传至七牛
                $imageName = 'erp/compress/'.date('Ymd').'/'.$imageOriginalName.'.jpg';
                $blob = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
                $this->app->core->api->Log()->writeLog('','upload compress image blob to qiniu start',$this->accessLog,$this->accessFunc);
                $qiniuUploadRet = $this->app->admin->core->api->Qiniu()->uploadBlobToQiniu($blob,$imageName);
                $this->app->core->api->Log()->writeLog($qiniuUploadRet,'upload compress image blob to qiniu end with',$this->accessLog,$this->accessFunc);
                if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
                    $backgroundUrl = $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url'];
                }else{
                    $backgroundUrl = '';
                }
                if(!empty($backgroundUrl)){
                    $imgMd5 = md5_file($backgroundUrl);
                    $createTaskRet = $this->app->nwdn->api->Helper()->createTask($backgroundUrl,$imgMd5 ,'consumer_erp_rmq');
                    $this->app->core->api->Log()->writeLog($createTaskRet,$v.'create task by nwdn end with',$this->accessLog,$this->accessFunc);
                    if(!empty($createTaskRet['taskid'])){
                        sleep(5);
                        $getTaskRet = $this->app->nwdn->api->Helper()->getTask($createTaskRet['taskid']);
                        if(!empty($getTaskRet['output_url'])){
                            error_log($getTaskRet['output_url']."\n",3,'D:\image\image_hdr_0918'.DIRECTORY_SEPARATOR.$v.'.log');
                        }

                    }

                }

            }
        }
    }

    private function saveImage($url,$filename)
    {
        $state = @file_get_contents($url,0,null,0,1);//获取网络资源的字符内容
        if($state){

            ob_start();//打开输出
            readfile($url);//输出图片文件
            $img = ob_get_contents();//得到浏览器输出
            ob_end_clean();//清除输出并关闭
            $size = strlen($img);//得到图片大小
            $fp2 = @fopen($filename, "a");
            fwrite($fp2, $img);//向当前目录写入图片文件，并重新命名
            fclose($fp2);
            return 1;
        }
        else{
            return 0;
        }
    }

    public function meituHeadReplaceAction(){
        $imageDir = "D:\image\init_head0920";
        $datas = scandir($imageDir);
        $i = 0;
        foreach ($datas as $v){
            if($v !="."  && $v !=".."   ){
                $i++;
                var_dump($i);
                $imageUrl = $imageDir.DIRECTORY_SEPARATOR.$v;
                $pathParts = pathinfo($imageUrl);
                $imageOriginalName = $pathParts['filename'].time();//获取图片名称
                //上传至七牛
                $imageName = 'erp/compress/'.date('Ymd').'/'.$imageOriginalName.'.jpg';
                $blob = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
                $limitSize = $this->app->core->api->Image()->sizeTrillion;
                $compressRet = $this->app->core->api->Image()->compressImage($blob,$limitSize*2);
                if(!empty($compressRet['image_blob'])){

                    $this->app->core->api->Log()->writeLog('','upload compress image blob to qiniu start',$this->accessLog,$this->accessFunc);
                    $qiniuUploadRet = $this->app->admin->core->api->Qiniu()->uploadBlobToQiniu($compressRet['image_blob'],$imageName);
                    $this->app->core->api->Log()->writeLog($qiniuUploadRet,'upload compress image blob to qiniu end with',$this->accessLog,$this->accessFunc);
                    //美图限制2MB
                    if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
                        $backgroundUrl = $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url'];
                        var_dump($backgroundUrl);
                        $this->app->core->api->Log()->writeLog($backgroundUrl,$imageUrl.' upload to qiniu ',$this->accessLog,$this->accessFunc);
                    }else{
                        $backgroundUrl = '';
                    }
                    if(!empty($backgroundUrl)){
                        $params = [
                            "parameter" => [
                                'rsp_media_type' => 'url',
                                'nType' => 1,
                                'nDistane' => 10,
                                'nSigma' => 50,
                            ],
                            "media_info_list" => [
                                [
                                    "media_data" => $backgroundUrl,
                                    "media_profiles" => ["media_data_type" => "url"]
                                ]
                            ],
                        ];
                        var_dump($params);
                        $data = $this->app->meitu->api->Helper()->headReplace($params);
                        var_dump($data);
                        $imageOutUrl =$data['media_info_list'][0]['media_data'];
                        $filname = 'D:\image\init_head0920_meitu_1_10_50'.DIRECTORY_SEPARATOR.$v;
                        $this->saveImage($imageOutUrl,$filname);
                    }

                }
            }
        }

    }
    public function aiFenGeSegmentEdgeAction(){
        $imageDir = "D:\image\init_head0920";
        $datas = scandir($imageDir);
        $i = 0;
        foreach ($datas as $v){
            if($v !="."  && $v !=".." ){
                sleep(1);
                $i++;
                var_dump($i.'--------------'.$v);
                $imageUrl = $imageDir.DIRECTORY_SEPARATOR.$v;
                $pathParts = pathinfo($imageUrl);
                $params['image_type'] = $pathParts['extension'];

                $blob = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
                $limitSize = $this->app->core->api->Image()->sizeTrillion;
                $compressRet = $this->app->core->api->Image()->compressImage($blob,$limitSize*2,2000,2000);
                $params['base64'] = $compressRet['image_base64str'] ?? '';
//                $compressRet = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
//                $params['base64'] = $compressRet ;
                $params['border'] = "0.01";
                $params['margin_color'] = '#ffffff';
                if(!empty($params['base64'])){
                    var_dump('will segment');
                    $this->app->core->api->Log()->writeLog($params['image_type'],'will segmentBorder',$this->accessLog,$this->accessFunc);
                    $data = $this->app->wxhj->api->Helper()->segmentBorder($params);
                    $this->app->core->api->Log()->writeLog($data,'segment segmentBorder ',$this->accessLog,$this->accessFunc);
                    var_dump($data);
                    $imageOutUrl =$data['data']['result'] ?? '';
                    var_dump($v.':   '.$imageOutUrl);
                    $this->app->core->api->Log()->writeLog($v.':   '.$imageOutUrl,'segmentBorder result ',$this->accessLog,$this->accessFunc);
                    if(!empty($imageOutUrl)){
                        $filname = 'D:\image\init_head0920_aifenge_edge_0.01'.DIRECTORY_SEPARATOR.$v;
                        $this->saveImage($imageOutUrl,$filname);
                    }
                }
            }
        }
    }

    public function aiFenGeSegmentAction(){
        $params['image_type'] = 'jpeg';
        $imageUrl = 'D:\image\image_wxsyb\image_20190904111134.jpg';
        $imageUrl = 'D:\image\image_wxsyb\image_qyt20190904112609.jpg';
        $imageUrl = 'D:\image\init_5868.jpg';//'init_mnls.jpeg'
        $imageUrl = 'D:\image\init_mnls.jpeg';
        $imageUrl = 'D:\image\init_compress_mnls.jpeg';
        $imageUrl = 'D:\image\image_wxsyb1009\image\image_20190904111134.jpg';
        $imageUrl = 'D:\image\image_wxsyb_all\image_wxsyb0918\image_zy20190904113019.jpg';
        /*
        $params['blob'] = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
        $limitSize = $this->app->core->api->Image()->sizeTrillion;
        $compressRet = $this->app->core->api->Image()->compressImage($params['blob'],$limitSize*2,2000,2000);
        $compressFilname ='D:\image\init_compress_mnls.jpeg';
        $this->app->core->api->Image()->saveBase64($compressRet['image_base64str'],$compressFilname);exit;
        */
//        $params['base64'] = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
        $pathParts = pathinfo($imageUrl);
        $params['image_type'] = $pathParts['extension'];
        $params['base64'] = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
        $this->app->core->api->Log()->writeLog($params['image_type'],'will segment',$this->accessLog,$this->accessFunc);
        $data = $this->app->wxhj->api->Helper()->segment($params);
        $this->app->core->api->Log()->writeLog($data,'segment result ',$this->accessLog,$this->accessFunc);
        var_dump($data);
        exit;

        //建表
        //通过唯一码获取七牛token api
        //通过唯一码获取ERP原图 ERP-api
        //face++相似度比对
        //保存比较结果（新增或更新）
        //通过唯一码查询比对结果 api
        $imageDir = "D:\image\init_aifenge_0926\init_head0926";
        $datas = scandir($imageDir);
        $i = 0;
        foreach ($datas as $v){
            if($v !="."  && $v !=".."){
                $i++;
                var_dump($i.'--------------'.$v);
                $imageUrl = $imageDir.DIRECTORY_SEPARATOR.$v;
                $pathParts = pathinfo($imageUrl);
                $params['image_type'] = $pathParts['extension'];

                /*
                $blob = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
                $limitSize = $this->app->core->api->Image()->sizeTrillion;
                $compressRet = $this->app->core->api->Image()->compressImage($blob,$limitSize*2,2000,2000);
                */
                $params['base64'] = $compressRet['image_base64str'] ?? '';
                $params['base64'] = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
                $params['return_rgba'] = 1;
                $params['is_crop_content'] = 1; //如果只需要保留人头的部分，透明部分裁剪掉，可以再加个参数：is_crop_content，设置为1
                if(!empty($params['base64'])){
                    var_dump('will segment');
                    /*
                    $compressFilname ='D:\image\init_aifenge\init_head0926_compress'.DIRECTORY_SEPARATOR.$v;
                    $this->app->core->api->Image()->saveBase64($params['base64'],$compressFilname);
                    */
                    $this->app->core->api->Log()->writeLog($params['image_type'],'will segment',$this->accessLog,$this->accessFunc);
                    $data = $this->app->wxhj->api->Helper()->segment($params);
                    $this->app->core->api->Log()->writeLog($data,'segment result ',$this->accessLog,$this->accessFunc);
                    $imageOutUrl =$data['alpha'] ?? '';
                    var_dump($v.':   '.$imageOutUrl);
                    $this->app->core->api->Log()->writeLog($v.':   '.$imageOutUrl,'segment result ',$this->accessLog,$this->accessFunc);
                    if(!empty($imageOutUrl)){
                        $filname ='D:\image\init_aifenge_0926\init_head0926_alpha'.DIRECTORY_SEPARATOR.$v;
                        $this->saveImage($imageOutUrl,$filname);
//                        $image_file = $compressFilname;
                        sleep(1);
                        $image_file = $imageUrl;
                        $alpha_file = $filname;
                        $result_png_file = 'D:\image\init_aifenge_0926\init_head0926_alpha_result'.DIRECTORY_SEPARATOR.$v;
                        $this->app->core->api->Log()->writeLog('',$image_file.' '.$alpha_file.' '.$result_png_file.' ',$this->accessLog,$this->accessFunc);
                        $this->composition_with_alpha($image_file, $alpha_file, $result_png_file);
                    }
                }
                break;
            }
        }

    }

    public function testAiFenGeAction(){
        $imageDir = "D:\image\init_head0920_aifenge";
        $datas = scandir($imageDir);
        $i = 0;
        foreach ($datas as $v){
            if($v !="."  && $v !=".." ){
                $imageUrl = $imageDir.DIRECTORY_SEPARATOR.$v;
                $pathParts = pathinfo($imageUrl);
                $result = $pathParts['filename'].'_result.'.$pathParts['extension'];
                $image_file = 'D:\image\init_head0920'.DIRECTORY_SEPARATOR.$v;
                $alpha_file = 'D:\image\init_head0920_aifenge'.DIRECTORY_SEPARATOR.$v;
                $result_png_file = 'D:\image\init_head0920_aifenge_result'.DIRECTORY_SEPARATOR.$result;
                $this->composition_with_alpha($image_file, $alpha_file, $result_png_file);
            }
        }
    }

    public function composition_with_alpha($image_file, $alpha_file, $result_png_file) {
//        $orig_image = imagecreatefromjpeg($image_file);
        $orig_image = imagecreatefrompng($image_file);
        $alpha_image = imagecreatefrompng($alpha_file);
        $wh  = getimagesize($image_file);
        $w   = $wh[0];
        $h   = $wh[1];
        imagesavealpha($orig_image, true);
        imageAlphaBlending($orig_image, false);
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgb = imagecolorat($orig_image, $x, $y);
                $alpha = imagecolorat($alpha_image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $color = imageColorAllocateAlpha($orig_image, $r, $g, $b, 127 - ($alpha / 2));
                imagesetpixel($orig_image, $x, $y, $color);
            }
        }
        imagepng($orig_image, $result_png_file);
        imagedestroy($orig_image);
        imagedestroy($alpha_image);
    }
    public function faceppCompareAction(){
        $image1 = 'D:\image\image_wxsyb\image_20190904094849.jpg';
        $base64_1 = $this->app->core->api->Image()->getBase64ByImageUrl($image1);
        $image2 = 'D:\image\image_wxsyb\image_20190904094849_merge_without.jpg';
        $base64_2 = $this->app->core->api->Image()->getBase64ByImageUrl($image2);
        if(!empty($base64_1) && !empty($base64_2)){
            $params = [
                'image_base64_1' => $base64_1,
                'image_base64_2' => $base64_2,
            ];
            $data = $this->app->face->api->Helper()->compareFace($params);
            var_dump($data);
            if(isset($data['confidence']) && isset($data['thresholds'])){
                var_dump('-------------------------------------');
                var_dump($data['confidence']);
                var_dump($data['thresholds']);
                var_dump('-------------------------------------');
            }

        }else{
            var_dump('empty base64');
        }


    }

    /**
     * 找到相同图片，移动到对应文件夹
     */
    public function findImageAndMoveAction(){
        echo '[';
        for($i=3;$i<31;$i++){
            echo "'{$i}',";
        }
        echo ']';exit;
        $imageDir = "D:\image\init_head0920";
        $datas = scandir($imageDir);
        $findImageDir = "D:\image\init_head0920_auto";
        $dImageDir = "D:\image\init_head0920_manual";
        $i = 0;
        foreach ($datas as $v){
            if($v !="."  && $v !=".." ){
                $i++;
                var_dump($i);
                $imageF = $findImageDir.DIRECTORY_SEPARATOR.$v;
                if(file_exists($imageF)){
                    $base64 = $this->app->core->api->Image()->getBase64ByImageUrl($imageF);
                    $filename = $dImageDir.DIRECTORY_SEPARATOR.$v;
                    $this->app->core->api->Image()->saveBase64($base64,$filename);
                }
            }
        }
    }
    public function uploadToQiniuAction(){
        $imageDir = 'D:\image\init_compare_face';
        $niren = 'BH450_S01_a001.png';
        $niren = 'BH450_S01_a002.png';
        $niren = 'BH450_S08_a007.png';
        $image1 = $imageDir.DIRECTORY_SEPARATOR.$niren;
        $image1 = 'https://nwdn-hd2.oss-cn-shanghai.aliyuncs.com/emtions/389f0d145c9d26bd5bf94bc4fe737771.png';
        $base64_1 = $this->app->core->api->Image()->getBase64ByImageUrl($image1);
        $user = 'BH450_S01_a001_input.png';
        $user = 'BH450_S01_a002_input.png';
        $user = 'BH450_S08_a007_input.png';
        $image2 = $imageDir.DIRECTORY_SEPARATOR.$user;
        $image2 = 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190918/image_qyt201909041126091568798647.jpg';
        $base64_2 = $this->app->core->api->Image()->getBase64ByImageUrl($image2);
        $params = [
            'image_base64_1' => $base64_1,
            'image_base64_2' => $base64_2,
        ];
        $data = $this->app->face->api->Helper()->compareFace($params);
        $this->app->core->api->Log()->writeLog($data,$image1.' vs '.$image2,$this->accessLog,$this->accessFunc);
        var_dump($data);

        $black = [
            'D:\image\init_black\init_black0923\Q190831194510044.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1908311945100441569288292.jpg',
            'D:\image\init_black\init_black0923\Q190831194510045.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1908311945100451569288295.jpg',
            'D:\image\init_black\init_black0923\Q190901033610044.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1909010336100441569288296.jpg',
            'D:\image\init_black\init_black0923\Q190901061510024.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1909010615100241569288298.jpg',
//            'D:\image\init_black\init_black0923\Q190901195010053.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1909011950100531569288299.jpg',
            'D:\image\init_black\init_black0923\Q190902141810026.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1909021418100261569288306.jpg',
        ];
        exit;
        $imageDir = "D:\image\init_black\init_black0923";
        $datas = scandir($imageDir);
        $i = 0;
        foreach ($datas as $v){
            if($v !="."  && $v !=".." ){
                $i++;
                var_dump($i);
                $inputImage = $imageDir.DIRECTORY_SEPARATOR.$v;
                $pathParts = pathinfo($inputImage);
                $imageOriginalName = $pathParts['filename'].time();//获取图片名称
                //上传至七牛
                $imageName = 'erp/compress/'.date('Ymd').'/'.$imageOriginalName.'.jpg';
                $blob = $this->app->core->api->Image()->getBlobByImageUrl($inputImage);
                $this->app->core->api->Log()->writeLog('','upload compress image blob to qiniu start',$this->accessLog,$this->accessFunc);
                $qiniuUploadRet = $this->app->admin->core->api->Qiniu()->uploadBlobToQiniu($blob,$imageName);
                $this->app->core->api->Log()->writeLog($qiniuUploadRet,'upload compress image blob to qiniu end with',$this->accessLog,$this->accessFunc);
                if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
                    $inputUrl = $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url'];
                }else{
                    $inputUrl = '';
                }
                echo "'".$inputImage."'=>"."\n";
                echo "'".$inputUrl."',"."\n";
            }
        }

    }
    public function testCreateFaceTaskAction(){
        $blacks = [
//            'D:\image\init_black\init_black0923\Q190831194510044_nwdn.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1908311945100441569288292.jpg',
//            'D:\image\init_black\init_black0923\Q190831194510045_nwdn.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1908311945100451569288295.jpg',
//            'D:\image\init_black\init_black0923\Q190901033610044_nwdn.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1909010336100441569288296.jpg',
//            'D:\image\init_black\init_black0923\Q190901061510024_nwdn.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1909010615100241569288298.jpg',
//            'D:\image\init_black\init_black0923\Q190901195010053.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1909011950100531569288299.jpg',
            'D:\image\init_black\init_black0923\Q190902141810026_nwdn.png'=> 'http://qiniu.wanjunjiaoyu.com/erp/compress/20190924/Q1909021418100261569288306.jpg',
        ];
        $sku = 'BH450';
        $ethnicity = 'S04 Dark African';
        foreach ($blacks as $filename=>$inputUrl){
            $taskData = [];
            if(empty($sku) || empty($ethnicity) || empty($inputUrl)){
                $this->app->core->api->Log()->writeLog('','Params is error ','consumer_erp_reject_error','log');exit;
            }
            $ethnicity = substr($ethnicity,0,3);
            $configInfo = $this->app->nwdn->config->sku->toArray();
            $emid = $configInfo[$sku][$ethnicity] ?? '';

            if(empty($emid)){
                $this->app->core->api->Log()->writeLog($sku.' and '.$ethnicity,'This sku and ethnicity have no configuration ','consumer_erp_reject_error','log');exit;
            }
            //创建换脸任务
            $this->app->core->api->Log()->writeLog('',' createFaceTask start by  '.$inputUrl.' and '.$emid,'consumer_erp_reject_access','log');
            $data = $this->app->nwdn->api->Helper()->createFaceTask($inputUrl,$emid);
            $this->app->core->api->Log()->writeLog($data,' createFaceTask end ','consumer_erp_reject_access','log');
            $taskId = $data['data']['taskid'] ?? '';
            if(empty($taskId)){
                $this->app->core->api->Log()->writeLog('','Create task failed ','consumer_erp_reject_error','log');exit;
            }
            //根据任务ID查询换脸任务(查询8次，每次间隔0.5秒)
            $outputUrl = '';
            for($i=0;$i<10;$i++){
                $data = $this->app->nwdn->api->Helper()->getFaceTask($taskId);
                if(isset($data['data']['output_url']) && !empty($data['data']['output_url'])){
                    $outputUrl = $data['data']['output_url'];
                    break;
                }
                usleep(500000);//0.5s
            }
            if(empty($outputUrl)){
                var_dump($taskId);
                $this->app->core->api->Log()->writeLog('','Please try again later. ','consumer_erp_reject_error','log');exit;
            }else{
                $taskData = [
                    'output_url' => $outputUrl
                ];
                $this->saveImage($outputUrl,$filename);
            }
            var_dump($taskData);
        }
    }

    public function testReflectAction(){
        /*
        //模板图 获取图片上传链接
        $url = $this->app->reflect->api->Helper()->getUploadUrl();
        // 上传图片
        $image = 'D:\image\image_template\BH450.jpg';//temp
        $file = $this->app->core->api->Image()->getBlobByImageUrl($image);
        $this->app->reflect->api->Helper()->uploadImageOther($url,$file);
        // 获取图片信息 并保存
        $imageUrl = $this->app->reflect->api->Helper()->getImageUrl($url);
        var_dump($imageUrl);
        $data = $this->app->reflect->api->Helper()->getImageInfo($imageUrl);
        $this->app->core->api->Log()->writeLog($data,' temp image info',$this->errorLog,$this->accessFunc);
        $imageIdTemp = $data['id'];
        $imageInfoIdTemp = $data['imageInfo']['id'];
        $tmp = $data['imageInfo']['faces'];
        foreach ($tmp as $faceId=>$v){
            $imageInfoIdTemp =$faceId;break;
        }
        */
        //
        //用户图 获取图片上传链接
        $url = $this->app->reflect->api->Helper()->getUploadUrl();
        var_dump($url);
        // 上传图片
        $image = 'D:\image\image_wxsyb_all\image_wxsyb\image_qyt20190904112609.jpg';//user qyt
        $image = 'D:\image\image_wxsyb_all\image_wxsyb\image_ld20190904094849.jpg';//user liuduo
        $image = 'D:\image\image_wxsyb_all\image_wxsyb\image_20190904111134.jpg';//user dongguomina
        $image = 'D:\image\image_wxsyb_all\image_wxsyb\image_zy20190904113019.jpg';//user zhangyu
        $image = 'D:\image\image_wxsyb_all\image_wxsyb\image_jzs20190904111613.jpg';//user jinzhongshuai
        $image = 'D:\image\image_wxsyb_all\image_wxsyb\image_lz20190904113002.jpg';//user liuze
        $image = 'D:\image\image_wxsyb_all\image_wxsyb\image_lc20190904112058.jpg';//user liuchao
        $file = $this->app->core->api->Image()->getBlobByImageUrl($image);
        if(empty($file)){
            var_dump('blob failed!!!');
            exit;
        }
        $this->app->reflect->api->Helper()->uploadImageOther($url,$file);
        // 获取图片信息 并保存
        $imageUrl = $this->app->reflect->api->Helper()->getImageUrl($url);
        var_dump($imageUrl);
        $data = $this->app->reflect->api->Helper()->getImageInfo($imageUrl);
        $this->app->core->api->Log()->writeLog($data,' user image info',$this->errorLog,$this->accessFunc);
        $imageIdUser = $data['id'];
        $imageInfoIdUser = $data['imageInfo']['id'];
        $tmp = $data['imageInfo']['faces'];
        foreach ($tmp as $faceId=>$v){
            $imageInfoIdUser =$faceId;break;
        }

        // 人脸融合
        $params = [
            "image_id" => 'cbcf257c-08fb-478f-89f0-4922d34f72c9',
            "facemapping" => [ 'bd2bfc86-05c0-4c3e-a72b-a3d71c859b4a' =>  [  $imageInfoIdUser ] ],
            "tumbler" => true,
        ];
        var_dump($params);
        $this->app->core->api->Log()->writeLog($params,' face fuse start',$this->errorLog,$this->accessFunc);
        $data = $this->app->reflect->api->Helper()->faceFuse($params);
        $this->app->core->api->Log()->writeLog($data,' face fuse end',$this->errorLog,$this->accessFunc);
        var_dump($data);
        var_dump($data['data']['image_path']);

        exit;



        $params = [
            "image_id" => "efdee860-9bf0-4652-96b1-8d5bb08fb17a",
            "facemapping" => [ "3d09d248-84cd-4460-8eec-aff2b01376a4" =>  [  "334bc4d9-3c69-4600-9cd3-a337274f9f8d" ] ],
            "tumbler" => true,
        ];
        var_dump($params);
        $data = $this->app->reflect->api->Helper()->faceFuse($params);
        var_dump($data);
        exit;
        /*
               $params = array(3) {
            ["image_id"]=>
  string(36) "efdee860-9bf0-4652-96b1-8d5bb08fb17a"
            ["facemapping"]=>
  array(1) {
                ["3d09d248-84cd-4460-8eec-aff2b01376a4"]=>
    array(1) {
                    [0]=>
      string(36) "334bc4d9-3c69-4600-9cd3-a337274f9f8d"
    }
  }
  ["tumbler"]=>
  bool(true)
};*/

        $data = '{"image_id": "efdee860-9bf0-4652-96b1-8d5bb08fb17a","facemapping": {"3d09d248-84cd-4460-8eec-aff2b01376a4": ["334bc4d9-3c69-4600-9cd3-a337274f9f8d"]},"tumbler": true}';
        $data = json_decode($data,true);
        var_dump($data);

        exit;
        $imageUrl = 'https://storage.googleapis.com/prod-reflect-images/images/inputs/c6b6187c-e2a2-45d8-a8a3-b69e211bca43.jpeg';
        $data = $this->app->reflect->api->Helper()->getImageInfo($imageUrl);
        $imageId = $data['id'];
        $imageInfoId = $data['imageInfo']['id'];
        var_dump($data);
        exit;
        $url = $this->app->reflect->api->Helper()->getUploadUrl();
        var_dump($url);

        $image = 'D:\image\image_wxsyb\image_20190904111134_get.jpg';
        $file = $this->app->core->api->Image()->getBlobByImageUrl($image);
        $this->app->reflect->api->Helper()->uploadImageOther($url,$file);

        exit;
        $data = "https://storage.googleapis.com/prod-reflect-images/images/inputs/9ba0b076-c066-404f-9ede-dd5327f028d6.jpeg?GoogleAccessId=reflect-images-creator@reflect-217613
.iam.gserviceaccount.com&Expires=1570500420&Signature=SC%2FhLV1hh%2BscE3o0nsUdG2ZqPg1HguN7dpixo%2FkM5gXKxKD8dKvcK2yl2QMxaJ4khppWWQqFa4%2FcxefQdrfEfQWzCBcVh65PY1Y%2FlBm6sUo3
OO5ToAj9%2BefyTCVs1T%2F%2BJaSHMq9LqjxYnFDrIL4xjWjqBxXyJmY8TQDofjLkr0DZxUk3zM2JqKTGm0ZJOMYk1fh%2FUXzaHP4fjcFpksW%2FXcOzvYc5uocnuQfnm4dF7XtR%2F39qzDpjUX%2F6iutR486vQG6O1tmmCE
s19r4v55O1askpLDVLTQ9t2cqICaAkpSo1PtvB0waZ6htjJx6wM%2FlLfPyKss0XJatPoy7cBCnmvQ=="
        ;
        // 获取图片上传链接 https://api.reflect.tech/api/faces/signedurl?extension=jpeg GET

        $tmp = explode('?',$data);
        var_dump($tmp);
        $tmp2 = basename($data);
        var_dump($tmp2);
        $tmp3 = explode('.com/',$data);
        $baseUrl = $tmp3[0].'.com/';
        $uri = '/'.$tmp3[1];

        // 上传图片 上一步获取到的链接，
        // header Accept"application/json, text/plain, */*" Content-Type "image/jpeg" body-binary  PUT

        // 获取图片ID https://api.reflect.tech/api/faces/addimage  header Content-Type"application/json" POST body-raw {
        //	"image_url":"https://storage.googleapis.com/prod-reflect-images/images/inputs/efdee860-9bf0-4652-96b1-8d5bb08fb17a.jpeg"
        // }

        // 获取图片ID https://api.reflect.tech/api/faces/addimage  header Content-Type"application/json" POST body-raw {
        //	"image_url":"https://storage.googleapis.com/prod-reflect-images/images/inputs/efdee860-9bf0-4652-96b1-8d5bb08fb17a.jpeg"
        // }

        // 人脸融合 https://api.reflect.tech/api/faces/swapfaces header Content-Type"application/json" POST {
        //	"image_id": "efdee860-9bf0-4652-96b1-8d5bb08fb17a",
        //	"facemapping": {
        //		"3d09d248-84cd-4460-8eec-aff2b01376a4": ["334bc4d9-3c69-4600-9cd3-a337274f9f8d"]
        //	},
        //	"tumbler": true
        //}


    }

    public function createBase64Action(){
        $imageDir = "D:\image\init_aifenge1009long";
        $datas = scandir($imageDir);
        $i = 0;
        $ret = [
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            100 => 0,
        ];
        foreach ($datas as $v){
            if($v !="."  && $v !=".." ){
                $i++;
                var_dump($i);
                $imageUrl = $imageDir.DIRECTORY_SEPARATOR.$v;
                var_dump($imageUrl);
                $pathParts = pathinfo($imageUrl);
                $params['image_type'] = $pathParts['extension'];
                $blob = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
                $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
                $limitSize = 2*$sizeTrillion;
                $compressRet = $this->app->core->api->Image()->compressImage($blob,$limitSize,2000,2000);
                if(empty($compressRet['image_base64str'])){
                    continue;
                }
                var_dump($compressRet['image_height'],$compressRet['image_width'],$compressRet['image_size'],$compressRet['image_size']/$sizeTrillion);
                $params['image_base64'] = $compressRet['image_base64str'];//$this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
//                error_log($params['image_base64'],3,'D:\phpStudy\PHPTutorial\WWW\Soufeel-AI\var\logger\test_time.log');
                $this->app->core->api->Log()->writeLog($params['image_type'],'will segment '.$v,$this->accessLog,$this->accessFunc);
                $data = $this->app->reflect->api->Helper()->testSegment($params);
                $this->app->core->api->Log()->writeLog($data,'segment result ',$this->accessLog,$this->accessFunc);
                if(!empty($data['error']) || $data['message'] != 'ok'){
                    $ret[100] += 1;
                }else{
                    $tmpData = $data['profiler']['default']['6112309d71c7034333dadabb8e35e53b'];
                    $tmpTime = trim($tmpData['time'],'ms');
                    $tmpTime = (float)$tmpTime;
                    var_dump($tmpTime);
                    if($tmpTime<3000){
                        $ret[2] += 1;
                    }elseif($tmpTime<4000){
                        $ret[3] += 1;
                    }elseif($tmpTime<5000){
                        $ret[4] += 1;
                    }elseif($tmpTime<6000){
                        $ret[5] += 1;
                        var_dump('------------');
                        $tmpLogData = ['size'=>$compressRet['image_size']/$sizeTrillion,'time'=>$tmpTime,$v];
                        $this->app->core->api->Log()->writeLog($tmpLogData,'>5s ',$this->accessLog,$this->accessFunc);
                        var_dump($compressRet['image_size']/$sizeTrillion);
                        var_dump($tmpTime);
                        var_dump($v);
                        var_dump('------------');
                    }else{
                        $ret[6] += 1;
                        var_dump('------------');
                        $tmpLogData = ['size'=>$compressRet['image_size']/$sizeTrillion,'time'=>$tmpTime,$v];
                        $this->app->core->api->Log()->writeLog($tmpLogData,'>5s ',$this->accessLog,$this->accessFunc);
                        var_dump($compressRet['image_size']/$sizeTrillion);
                        var_dump($tmpTime);
                        var_dump($v);
                        var_dump('------------');
                    }
                }
            }
        }
        var_dump($i);
        var_dump($ret);
    }

    public function testCommitAction(){
        $this->db->begin();

        $robot = new FaceppDetectImages();

        $robot->host      = "WALL·E";
        $robot->created_at = date("Y-m-d");

        // The model failed to save, so rollback the transaction
        if ($robot->save() === false) {
            $this->db->rollback();
            return;
        }

        $robotPart = new FaceppDetectSingleFace();

        $robotPart->images_id = $robot->id;
        $robotPart->emotion   = "head";

        // The model failed to save, so rollback the transaction
        if ($robotPart->save() === false) {
            $this->db->rollback();

            return;
        }

        // Commit the transaction
        $this->db->commit();

    }

    public function testNoticeParamErrorAction(){
        $tmp = 'https://oss.mtlab.meitu.com/mtopen/rF5GIhp5ReLKgLV91CKj5BO1q2FTLMmc/MTU3MDg0OTIwMA==/d30b8667-60e0-4376-8f77-7c0d90f20653.jpg';//两个人
        $tmp = '
        {"data":{"queue_data":"{\"0\":{\"photo_ai\":\"https:\\\/\\\/pic.stylelab.com\\\/share\\\/custom_product_photos\\\/original\\\/20191012\\\/20191012075057YkVjdA-CQB25-1.png\",\"unique_code\":\"X191012080010003\",\"item_id\":\"179835\"},\"type\":\"SERP\"}","task_type":"aiimagedownload","taskmgr_sign":"747F52996C3C037AB467D932BDEE6E5C"},"url":"http:\/\/120.76.221.184:8087\/index.php\/openapi\/autotask\/service\/"}
        ';
        $data = '{"time_used":780,"faces":[{"attributes":{"emotion":{"sadness":0.007,"neutral":0,"disgust":0,"anger":0,"surprise":0,"fear":0,"happiness":99.993},"headpose":{"yaw_angle":-13.395739,"pitch_angle":4.844644,"roll_angle":-82.136345},"blur":{"blurness":{"threshold":50,"value":1.999},"motionblur":{"threshold":50,"value":1.999},"gaussianblur":{"threshold":50,"value":1.999}},"gender":{"value":"Female"},"age":{"value":33},"facequality":{"threshold":70.1,"value":63.862},"ethnicity":{"value":"WHITE"}},"face_rectangle":{"width":71,"top":709,"left":934,"height":71},"face_token":"46243a126275e12c6cdd753cc20d3230"}],"image_id":"4QnJ1XEL5eS+idUQ0Dfolg==","request_id":"1570839247,39d3b72c-7612-4c40-83a8-ed1f45764ee6","face_num":1}';
        $faceResult = json_decode($data,true);
        if(empty($faceResult) || isset($faceResult['error_message'])){
            //请求face++ api 有误 原样返回
            $canAck = false;
            var_dump('error');
        }else{
            $canCreateTask = $this->app->face->api->Helper()->isBlur($faceResult);
            if($canCreateTask){
                //尺寸满足超分要求
                //模糊 调nwdn api 进行超分(20MB 2048*2048)
                var_dump('chao fen');
            }else{
                //无需超分 返回原图
                var_dump('no chao fen');
            }
            /*
             * array(2) {
  [
    "timeStamp"
  ]=>
  string(10) "1570866325"
  [
    "taskid"
  ]=>
  string(32) "3b2809b14d2990a20ca8cede9bc426fa"
}
string(96) "eyJ0aW1lU3RhbXAiOiIxNTcwODY2MzI1IiwidGFza2lkIjoiM2IyODA5YjE0ZDI5OTBhMjBjYThjZWRlOWJjNDI2ZmEifQ=="
array(2) {
  [
    "token"
  ]=>
  string(42) "app_token_24bfd3c995ca78a966b3a0308e6d7610"
  [
    "md5"
  ]=>
  string(32) "89e8455d1f1b95e4025818014a6a2b9b"
  string(42) "app_token_24bfd3c995ca78a966b3a0308e6d7610"
  string(32) "89e8455d1f1b95e4025818014a6a2b9b"
  string(42) "app_token_24bfd3c995ca78a966b3a0308e6d7610"
  string(32) "4fa8cb6db389ea9da7af3a5c70a3d70f"
}
             *
             *
             *
             *
             * array(2) {
  [
    "timeStamp"
  ]=>
  string(10) "1570866397"
  [
    "taskid"
  ]=>
  string(32) "3b2809b14d2990a20ca8cede9bc426fa"
}
string(96) "eyJ0aW1lU3RhbXAiOiIxNTcwODY2Mzk3IiwidGFza2lkIjoiM2IyODA5YjE0ZDI5OTBhMjBjYThjZWRlOWJjNDI2ZmEifQ=="
array(2) {
  [
    "token"
  ]=>
  string(42) "app_token_24bfd3c995ca78a966b3a0308e6d7610"
  [
    "md5"
  ]=>
  string(32) "4fa8cb6db389ea9da7af3a5c70a3d70f"
  string(42) "app_token_24bfd3c995ca78a966b3a0308e6d7610"
  string(32) "4fa8cb6db389ea9da7af3a5c70a3d70f"
}
             *
             *
             *
             *
             * */
        }

    }

}
