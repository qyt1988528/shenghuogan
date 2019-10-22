<?php
namespace Core\Command;

use MDK\Exception;
use Phalcon\Exception as PException;
use MDK\Console\AbstractCommand;
use MDK\Console\CommandInterface;
use MDK\Console\ConsoleUtil;
use MDK\Exception as MException;
use PhpAmqpLib\Exchange\AMQPExchangeType;

/**
 * Test command.
 *
 * @CommandName(['useful_deal'])
 * @CommandDescription('Test management.')
 */
class ConsumerNotExist extends AbstractCommand implements CommandInterface{
    public $accessLog = 'consumer_task111_access';
    public $errorLog = 'consumer_task111_error';
    public $accessFunc = 'log';
    public $errorFunc = 'error';

    public function uploadNirenBh450Action(){
        $imageDir = "D:\image\init_niren_bh450";
        $datas = scandir($imageDir);
        $i = 0;

        foreach ($datas as $v){
            if($v !="."  && $v !=".." ){
                $i++;
                var_dump($i);
                $imageUrl = $imageDir.DIRECTORY_SEPARATOR.$v;
                $file = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
                $url = $this->app->reflect->api->Helper()->getUploadUrl();
                // 上传图片
                $this->app->reflect->api->Helper()->uploadImageOther($url,$file);
                // 获取图片信息 并保存
                $imageUrl = $this->app->reflect->api->Helper()->getImageUrl($url);
                $data = $this->app->reflect->api->Helper()->getImageInfo($imageUrl);
                $this->app->core->api->Log()->writeLog($data,' temp image info',$this->accessLog,$this->accessFunc);
                $imageIdTemp = $data['id'];
                $imageInfoIdTemp = $data['imageInfo']['id'];
                $tmp = $data['imageInfo']['faces'];
                foreach ($tmp as $faceId=>$tv){
                    $imageInfoIdTemp =$faceId;break;
                }
                $data = [
                    'image_id' => $imageIdTemp,
                    'face_id' => $imageInfoIdTemp,
                ];
                $this->app->core->api->Log()->writeLog($data,' temp image&face id '.$v,$this->accessLog,$this->accessFunc);
                var_dump($imageUrl);

            }
        }

    }

    public function testApiReflectFaceFuseAction(){
        $imageUrl = 'D:\image\image_wxsyb_all\image_wxsyb0918\image_zy20190904113019.jpg';
        $file = $this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
//        $file = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
        if(empty($file)){
            var_dump('no image file');
        }
        $params = [
            'sku' => 'BH450',
            'ethnicity' => 'aS01',
            'image_base64' => $file,
        ];
        $data = $this->app->reflect->api->Helper()->testReflectFaceFuse($params);
        $this->app->core->api->Log()->writeLog($data,'reflect facefuse result ',$this->accessLog,$this->accessFunc);
        var_dump($data);

    }

    public function testMeituHeadProTimeAction(){
        $imageDir = "D:\image\init_aifenge1009";
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
        $ret2 = [
            2 => [],
            3 => [],
            4 => [],
            5 => [],
            6 => [],
            100 => [],
        ];
        foreach ($datas as $v){
            if($v !="."  && $v !=".." ){
                $i++;
                var_dump('++++++++++++++++++++++++++');
                var_dump($i);
                $imageUrl = $imageDir.DIRECTORY_SEPARATOR.$v;
                var_dump($imageUrl);
                $pathParts = pathinfo($imageUrl);
                $params['image_type'] = $pathParts['extension'];
                $blob = $this->app->core->api->Image()->getBlobByImageUrl($imageUrl);
                $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
                $limitSize = 1.5*$sizeTrillion;
                $compressRet = $this->app->core->api->Image()->compressImage($blob,$limitSize,2000,2000);
                if(empty($compressRet['image_base64str'])){
                    continue;
                }
                var_dump($compressRet['image_height'],$compressRet['image_width'],$compressRet['image_size'],$compressRet['image_size']/$sizeTrillion);
                $params['image_base64'] = $compressRet['image_base64str'];//$this->app->core->api->Image()->getBase64ByImageUrl($imageUrl);
//                error_log($params['image_base64'],3,'D:\phpStudy\PHPTutorial\WWW\Soufeel-AI\var\logger\test_time.log');
                $this->app->core->api->Log()->writeLog($params['image_type'],'will meitu head '.$v,$this->accessLog,$this->accessFunc);
                $data = $this->app->reflect->api->Helper()->testMeituHead($params);
                $this->app->core->api->Log()->writeLog($data,'meitu head result ',$this->accessLog,$this->accessFunc);
                if(!empty($data['error']) || $data['message'] != 'ok'){
                    $ret[100] += 1;
                }else{
                    $tmpData = $data['profiler']['default']['455b05905493730802fcca91f2b665c8'];
                    $tmpTime1 = trim($tmpData['time'],'ms');
                    $tmpTime = (float)$tmpTime1;
                    var_dump($tmpTime1);
                    if($tmpTime<3000){
                        $ret[2] += 1;
                        $ret2[2][] = $tmpTime1;
                    }elseif($tmpTime<4000){
                        $ret[3] += 1;
                        $ret2[3][] = $tmpTime1;
                    }elseif($tmpTime<5000){
                        $ret[4] += 1;
                        $ret2[4][] = $tmpTime1;
                    }elseif($tmpTime<6000){
                        $ret[5] += 1;
                        $ret2[5][] = $tmpTime1;
                        var_dump('------------');
                        $tmpLogData = ['size'=>$compressRet['image_size']/$sizeTrillion,'time'=>$tmpTime,$v];
                        $this->app->core->api->Log()->writeLog($tmpLogData,'>5s ',$this->accessLog,$this->accessFunc);
                        var_dump($compressRet['image_size']/$sizeTrillion);
                        var_dump($tmpTime);
                        var_dump($v);
                        var_dump('------------');
                    }else{
                        $ret[6] += 1;
                        $ret2[6][] = $tmpTime1;
                        var_dump('------------');
                        $tmpLogData = ['size'=>$compressRet['image_size']/$sizeTrillion,'time'=>$tmpTime,$v];
                        $this->app->core->api->Log()->writeLog($tmpLogData,'>5s ',$this->accessLog,$this->accessFunc);
                        var_dump($compressRet['image_size']/$sizeTrillion);
                        var_dump($tmpTime);
                        var_dump($v);
                        var_dump('------------');
                    }
                }
                var_dump('++++++++++++++++++++++++++');
            }
        }
        var_dump($i);
        var_dump($ret);
        var_dump($ret2);
        $this->app->core->api->Log()->writeLog($ret,' ret ',$this->accessLog,$this->accessFunc);
    }


}
