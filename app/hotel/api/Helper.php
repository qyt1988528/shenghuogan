<?php
namespace Face\Api;

use Face\Model\FaceppDetectImages;
use Face\Model\FaceppDetectSingleFace;
use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

class Helper extends Api
{
    public $qiniuThumbnailKey = '-soufeel_super_image_ai';//七牛压缩样式符
    public $qiniuThumbnailInfoKey = '-soufeel_super_image_ai_info';//七牛压缩后图片信息样式符
    const IMAGE_FILE_TOO_LARGE = 'IMAGE_FILE_TOO_LARGE';
    const NO_FACE_FOUND= 'NO_FACE_FOUND';
    const BAD_FACE = 'BAD_FACE';
//    const IMAGE_DOWNLOAD_TIMEOUT = 'IMAGE_DOWNLOAD_TIMEOUT';
    public $errorMessage = [
        self::IMAGE_FILE_TOO_LARGE,
        self::NO_FACE_FOUND,
        self::BAD_FACE,
//        self::IMAGE_DOWNLOAD_TIMEOUT,
    ];
    const NWDN_FACE_TASK_LIMIT_ANGLE = 15;//你我当年 换脸任务 限制角度值
    private $_client;
    public function __construct() {
        $this->_client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->app->face->config->face->baseUri,
            // You can set any number of default request options.
            'timeout'  => $this->app->face->config->face->timeout,
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
                $result = $response->getBody()->getContents();
                $result = json_decode($result,true);
                if(isset($result['error_message']) && in_array($result['error_message'],$this->errorMessage)){
                }else{
                    $msg = $this->translate->_('Network Error. Please Try Again Later!');
                    throw new \Exception($msg,106);
                }
            }
            /*
            if(isset($result['error_message'])){
                throw new \Exception($result['error_message'],1);
            }*/
            return $response;
        };
    }
    /**
     * 获取不含data:image/jpeg;base64,的base64图片
     * @param $image
     * @return bool|string
     */
    public function getBaseImage($image)
    {
        if(($start = strpos($image, ",")) !== false){
            $start ++;
            $image = substr($image, $start);
        }
        return $image;
    }

    /**
     * 获取包含data:image/jpeg;base64,的base64图片
     * @param $image
     * @return string
     */
    public function getWebBaseImage($image)
    {
        if($start = strpos($image, ",") === false){
            $image = 'data:image/jpeg;base64,'.$image;
        }
        return $image;
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
     *
     *  如果同时传入了 image_url、image_file 和 image_base64 参数，本API使用顺序为 image_file 优先，image_url 最低。
     * gender  - 性别
     * age - 年龄
     * headpose - 头部姿势
     * facequality - 脸部质量
     * blur - 脸部模糊
     * emotion - 情绪
     * ethnicity - 有色人种
     * @param $image
     * @return mixed|\Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function faceDetect($image,$source='',$saveToDb = true){
        $configInfo = $this->app->face->config->face->toArray();
        $params = [
            'api_key' => $configInfo['apiKey'],
            'api_secret' => $configInfo['apiSecret'],
            'return_attributes' => 'gender,age,headpose,blur,facequality,emotion,ethnicity,eyestatus,glass',
        ];
        $params = array_merge($params,$image);
        //将$image插入数据库 获得image表的id
        if($saveToDb){
            $imagesId = $this->logDetectCreate($image,$source);
        }
        $response = $this->_client->request('POST', '/facepp/v3/detect',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        //根据id 将result 更新到 image表里
        //根据image_id 将result 添加到 single_face表里
        if($saveToDb){
            $this->logDetectUpdate($imagesId,$result);
        }

        return $result;
    }

    /**
     * 请求facepp_detect—api前创建相应记录
     * @param $image
     * @param string $source
     * @return int
     */
    public function logDetectCreate($image,$source=''){
        if(!empty($source)){
            $tmpDatas = explode('_',$source);
            $data['host'] = $tmpDatas[0];
            $data['path'] = $tmpDatas[1];
        }else{
            $data['host'] = $this->request->getHeader('origin');
            $data['host'] = parse_url($data['host']);
            $data['host'] = isset($data['host']['host'])?$data['host']['host']:'';
            $data['path'] = $this->request->getURI();
            $data['path'] = parse_url($data['path']);
            $data['path'] = isset($data['path']['path']) ?$data['path']['path'] :'';
        }
        $insertData = array_merge($data,$image);
//        $insertData['created_at'] = $insertData['updated_at'] = date('Y-m-d H:i:s');
        $model = new FaceppDetectImages();
        $model->create($insertData);
        $id = isset($model->id) ? $model->id : 0;
        return $id;
    }

    /**
     * 当获取到facepp结果时，更新图片表，再根据识别的各个人脸数据分别插入到single_face表中
     * @param $imagesId
     * @param $data
     */
    public function logDetectUpdate($imagesId,$data){
        //先查询获取之前任务对应的id
        $updateModel = FaceppDetectImages::findFirstById($imagesId);
        $updateData = [
            'id' => $imagesId,
            'blur' => $this->isBlur($data),
            'face_num' => $data['face_num'] ?? 0,
            'facepp_result' => json_encode($data),
        ];
//        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $updateModel->update($updateData);

        foreach ($data as $k=>$v){
            if($k=='faces'){
                foreach ($v as $kv=>$vv){
                    $singleFaceData = json_encode($vv);
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
                        'init_face_data' => $singleFaceData,
                        'face_token' => $vv['face_token'] ?? '',
                    ];
                    $insertData['face_rectangle'] = $insertData['face_rectangle_top'].','.$insertData['face_rectangle_left'].','.$insertData['face_rectangle_width'].','.$insertData['face_rectangle_height'];
                    $model = new FaceppDetectSingleFace();
                    $model->create($insertData);
                }
            }
        }

    }


    /**
     * 人脸个数为0|均不模糊 --false
     * 识别出的人脸有1个大于模糊阈值 认为模糊 --true
     * @param $data
     * @return bool
     */
    public function isBlur($data){
        $blurValue = 0;
        if(!empty($data) && !isset($data['error_message'])){
            if(!empty($data['faces'])){
                foreach ($data['faces'] as $df){
                    if(isset($df['attributes']['blur'])){
                        if($df['attributes']['blur']['blurness']['value'] == $df['attributes']['blur']['motionblur']['value'] && $df['attributes']['blur']['gaussianblur']['value'] == $df['attributes']['blur']['motionblur']['value']){
                            //模糊数据一致
                            if($df['attributes']['blur']['blurness']['value'] > $df['attributes']['blur']['blurness']['threshold']){
                                $blurValue = -1;//模糊
                            }else{
                                if($blurValue != -1){
                                    $blurValue = 1;//不模糊
                                }
                            }
                        }elseif($df['attributes']['blur']['blurness']['value'] > $df['attributes']['blur']['blurness']['threshold']
                            || $df['attributes']['blur']['motionblur']['value'] > $df['attributes']['blur']['motionblur']['threshold']
                            || $df['attributes']['blur']['gaussianblur']['value'] > $df['attributes']['blur']['gaussianblur']['threshold']){
                            //模糊数据一致
                            $blurValue = -1;//模糊
                        }elseif($df['attributes']['blur']['blurness']['value'] < $df['attributes']['blur']['blurness']['threshold']
                            && $df['attributes']['blur']['motionblur']['value'] < $df['attributes']['blur']['motionblur']['threshold']
                            && $df['attributes']['blur']['gaussianblur']['value'] < $df['attributes']['blur']['gaussianblur']['threshold']){
                            if($blurValue != -1){
                                $blurValue = 1;//不模糊
                            }
                        }
                    }
                }
            }
        }
        $canCreateTask = $blurValue==-1 ? true : false;
        return $canCreateTask;
    }

    public function getFaceSets(){
        $configInfo = $this->app->face->config->face->toArray();
        $params = [
            'api_key' => $configInfo['apiKey'],
            'api_secret' => $configInfo['apiSecret'],
        ];
        $response = $this->_client->request('POST', '/facepp/v3/faceset/getfacesets',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);

        return $result;
    }
    public function getDetail($facesetToken){
        $configInfo = $this->app->face->config->face->toArray();
        $params = [
            'api_key' => $configInfo['apiKey'],
            'api_secret' => $configInfo['apiSecret'],
            'faceset_token' => $facesetToken
        ];
        $response = $this->_client->request('POST', '/facepp/v3/faceset/getdetail',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result;
    }

    public function facesetUpdate(){
        $configInfo = $this->app->face->config->face->toArray();
        $params = [
            'api_key' => $configInfo['apiKey'],
            'api_secret' => $configInfo['apiSecret'],
        ];
        $response = $this->_client->request('POST', '/facepp/v3/faceset/update',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * faceset_token
     * outer_id
     *
     * face_tokens 人脸标识 face_token 组成的字符串，可以是一个或者多个，用逗号分隔。最多不超过5个face_token
     * @return mixed|\Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addFace($facesetToken,$faceTokens){
        $configInfo = $this->app->face->config->face->toArray();
        $params = [
            'api_key' => $configInfo['apiKey'],
            'api_secret' => $configInfo['apiSecret'],
            'faceset_token' => $facesetToken,
            'face_tokens' => $faceTokens,
        ];
        $response = $this->_client->request('POST', '/facepp/v3/faceset/addface',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result;
    }

    public function createFaceSetToken(){
        $configInfo = $this->app->face->config->face->toArray();
        $params = [
            'api_key' => $configInfo['apiKey'],
            'api_secret' => $configInfo['apiSecret'],
        ];
        $response = $this->_client->request('POST', '/facepp/v3/faceset/create',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result;
    }


    public function analyze($faceTokens){
        $configInfo = $this->app->face->config->face->toArray();
        $params = [
            'api_key' => $configInfo['apiKey'],
            'api_secret' => $configInfo['apiSecret'],
            'return_attributes' => 'gender,age,headpose,blur,facequality,emotion,ethnicity',
            'face_tokens' => $faceTokens
        ];
        $response = $this->_client->request('POST', '/facepp/v3/face/analyze',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * facepp 人脸融合接口
     * @param $params
     * @return mixed|\Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function mergeFace($params){
        $configInfo = $this->app->face->config->face->toArray();
        $data = [
            'api_key' => $configInfo['apiKey'],
            'api_secret' => $configInfo['apiSecret'],
            'template_url' => $params['template_url'] ?? '',
            'template_base64' => $params['template_base64'] ?? '',
            'template_rectangle' => $params['template_rectangle'] ?? '',
            'merge_url' => $params['merge_url'] ?? '',
            'merge_base64' => $params['merge_base64'] ?? '',
            'merge_rectangle' => $params['merge_rectangle'] ?? '',
            'merge_rate' => $params['merge_rate'] ?? '',
        ];
        foreach ($data as $k=>$v){
            if(empty($v)){
                unset($data[$k]);
            }
        }
        $response = $this->_client->request('POST', '/imagepp/v1/mergeface',[
            "form_params" => $data,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 人脸融合pro
     * 根据所传sku，读取配置的模板图
     * 获取模板图的base64str（保存至文件，以便后续调用）
     * 调用上面的facepp人脸融合接口
     * 返回融合后的base64str
     * @param $params
     * @throws \Exception
     */
    public function mergeFacePro($params){
        //通过sku找模板图
        $skuInfo = $this->app->face->config->sku->toArray();
        $sku = $params['sku'] ?? '';
        if(empty($sku) || !isset($skuInfo[$sku])){
            //该产品不支持预览，因缺少模板图，请联系客服
            $result['code'] = 102;
            $result['msg'] = $this->translate->_('Preview is not accessed due to website related problem. Please contact customer support!');
            throw new \Exception($result['msg'],$result['code']);
        }
        $templateUrl = $skuInfo[$sku];
        $data = pathinfo($templateUrl);
        //判断文件是否存在，不存在写文件，存在直接读文件base64str
        $filename = $this->app->core->api->Log()->getSkuFilename($data['filename']);
        if(file_exists($filename)){
            $base64str = file_get_contents($filename);
        }else{
            $qiniuUrl = $templateUrl.$this->qiniuThumbnailKey;
            $base64str = $this->app->core->api->Image()->getBase64ByImageUrl($qiniuUrl);
            $this->app->core->api->Log()->writeSkuLog($base64str,$data['filename']);
        }
        $params['template_base64'] = $base64str;
        if(!empty($params['image_base64'])){
            $params['merge_base64'] = $params['image_base64'];
        }else{
            $params['merge_url'] = $params['image_url'].$this->qiniuThumbnailKey;
        }
        $data = $this->mergeFace($params);
        if(empty($data)){
            $result['code'] = 103;
            $result['msg'] = $this->translate->_('Network Error. Please Try Again Later!');
            throw new \Exception($result['msg'],$result['code']);
        }
        if(isset($data['error_message'])){
            if($data['error_message'] == self::IMAGE_FILE_TOO_LARGE){
                $result['code'] = 104;
                $result['msg'] = $this->translate->_('Picture is too large, please change and try again.');
                throw new \Exception($result['msg'],$result['code']);
            }elseif($data['error_message'] == self::NO_FACE_FOUND || $data['error_message'] == self::BAD_FACE){
                $result['code'] = 105;
                $result['msg'] = $this->translate->_('No Face Detected. Please change into picture showing the face clearly!');
                throw new \Exception($result['msg'],$result['code']);
            }
        }
        $result = [
            'base64str' => $data['result'],
        ];
        return $result;
    }

    /**
     * 根据各个表情的值，获取其中值最大的表情字符
     * @param $arr
     * @return int|string
     */
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

    /**
     * 获取单个脸部的模糊识别结果
     * 1-模糊，0-不模糊，-1-数据有误或未识别出人脸
     * @param $blurData
     * @return int
     */
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

    /**
     * 识别传入图片中人体的完整轮廓，进行人形抠像
     * @param $image
     * return_grayscale 0：不返回灰度图，仅返回人像图片 1：返回灰度图及人像图片 ,2：只返回灰度图
     * @return mixed|\Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getFace($image){
        $configInfo = $this->app->face->config->face->toArray();
        $params = [
            'api_key' => $configInfo['apiKey'],
            'api_secret' => $configInfo['apiSecret'],
            'return_grayscale' => $image['return_grayscale'] ?? 0,
        ];
        $params = array_merge($params,$image);
        $response = $this->_client->request('POST', '/humanbodypp/v2/segment',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);

        return $result;
    }

    /**
     * 人脸比对
     * @param $image
     * @return mixed|\Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function compareFace($image){
        $configInfo = $this->app->face->config->face->toArray();
        $params = [
            'api_key' => $configInfo['apiKey'],
            'api_secret' => $configInfo['apiSecret'],
        ];
        $params = array_merge($params,$image);
        $response = $this->_client->request('POST', '/facepp/v3/compare',[
            "form_params" => $params,
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);

        return $result;
    }

    /**
     * 根据facepp人脸比对结果，判断是否为同一人
     * @param $data
     * @return int
     */
    public function isSame($data){
        $isSame = 0;
        if(!empty($data) && isset($data['confidence']) && isset($data['thresholds'])){
            //如果置信值低于“千分之一”阈值则不建议认为是同一个人；如果置信值超过“十万分之一”阈值，则是同一个人的几率非常高。
            if($data['confidence'] > $data['thresholds']['1e-5']){
                $isSame = 1;
            }
            if($data['confidence'] < $data['thresholds']['1e-3']){
            }
        }
        return $isSame;
    }
    /**
     * 脸部姿态判断
     * 抬头、旋转（平面旋转）、摇头，这3个维度均为±15°
     * 0，初始化
     * 1，满足要求
     * 2，不满足要求（+）
     * 3，不满足要求（-）
     * @param $data
     * @return bool
     */
    public function headPose($data){
        $ret = [
            'pitch_angle' => 0,
            'pitch_angle_message' => '',
            'roll_angle' => 0,
            'roll_angle_message' => '',
            'yaw_angle' => 0,
            'yaw_angle_message' => '',
            'message' => ''
        ];

        if(!empty($data) && !isset($data['error_message'])){
            if(!empty($data['faces'])){
                foreach ($data['faces'] as $df){
                    if(isset($df['attributes']['headpose'])){
                        $pitchAngle = (float)$df['attributes']['headpose']['pitch_angle'];//pitch_angle：抬头
                        if(abs($pitchAngle) >= self::NWDN_FACE_TASK_LIMIT_ANGLE){
                            //低头pitch+
                            //抬头pitch-
                            if($pitchAngle<0){
                                $ret['pitch_angle'] = 3;
                                $ret['pitch_angle_message'] = 'head up';//抬头
                            }else{
                                $ret['pitch_angle'] = 2;
                                $ret['pitch_angle_message'] = 'head down';//低头
                            }
                        }else{
                            $ret['pitch_angle'] = 1;
                        }
                        $rollAngle = (float)$df['attributes']['headpose']['roll_angle'];//roll_angle：旋转（平面旋转）
                        if(abs($rollAngle) >= self::NWDN_FACE_TASK_LIMIT_ANGLE){
                            //左旋转roll+
                            //右旋转roll-
                            if($rollAngle<0){
                                $ret['roll_angle'] = 3;
                                $ret['roll_angle_message'] = 'right tilt';//右旋转
                            }else{
                                $ret['roll_angle'] = 2;
                                $ret['roll_angle_message'] = 'left tilt';//左旋转
                            }
                        }else{
                            $ret['roll_angle'] = 1;
                        }
                        $yawAngle = (float)$df['attributes']['headpose']['yaw_angle'];//yaw_angle：摇头
                        if(abs($yawAngle) >= self::NWDN_FACE_TASK_LIMIT_ANGLE){
                            //左摇头yaw+
                            //右摇头yaw-
                            if($yawAngle<0){
                                $ret['yaw_angle'] = 3;
                                $ret['yaw_angle_message'] = 'right shake';//右摇头
                            }else{
                                $ret['yaw_angle'] = 2;
                                $ret['yaw_angle_message'] = 'left shake';//左摇头
                            }
                        }else{
                            $ret['yaw_angle'] = 1;
                        }
                        break;
                    }
                }
            }
        }
        $tmpMessage = '';
        $tmpMessage = !empty($ret['pitch_angle_message']) ? $tmpMessage.$ret['pitch_angle_message'].',': '';
        $tmpMessage = !empty($ret['roll_angle_message']) ? $tmpMessage.$ret['roll_angle_message'].',': '';
        $tmpMessage = !empty($ret['yaw_angle_message']) ? $tmpMessage.$ret['yaw_angle_message'].',': '';
        $tmpMessage = trim($tmpMessage,',');
        if(!empty($tmpMessage)){
            $ret['message'] = 'We have detected that your head tilt ('.$tmpMessage.') angle is too large. Please face the camera and take a new picture.';
            $ret['message'] = $this->translate->_($ret['message']);
        }
        return $ret;
    }

    /**
     * 根据人脸识别结果，获取识别出的人脸个数
     * @param $data
     * @return int
     */
    public function faceNum($data){
        $faceNum = 0;
        if(!empty($data) && !isset($data['error_message'])){
            $faceNum = $data['face_num'] ?? 0;
        }
        return $faceNum;
    }

}