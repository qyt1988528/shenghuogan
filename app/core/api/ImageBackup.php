<?php
namespace Core\Api;

class Image {

    public $rateOriginal = 1;
    public $sizeTrillion = 1048576;//1MB=1048576bytes

    /**
     * 压缩图片v1.1
     * @param $imageBlobOriginal 图片二进制
     * @param $limitSize 限制大小bytes
     * @param int $limitWidth 限制宽
     * @param int $limitHeight 限制高
     * @param int $heightMultiplyWidth 限制长宽乘积
     * @param int $quality 质量
     * @param int $repeatCount 尝试压缩的次数
     * @return array
     * @throws \ImagickException
     */
    public function  compressImage($imageBlobOriginal,$limitSize,$limitWidth=0,$limitHeight=0,$heightMultiplyWidth=0,$quality=80,$repeatCount=8){
        $result = [];
        $originalRet = [];
        for($count=0;$count<$repeatCount;$count++){
            $result = $this->compressImageOld($imageBlobOriginal,$limitSize,$limitWidth,$limitHeight,$heightMultiplyWidth,$quality,$count);
            if(!empty($result)){
                if(empty($originalRet)){
                    //记录图片的初始数据，超分传图可能会用到原图数据
                    $originalRet = $result;
                }
                if($result['image_size']<$limitSize){
                    break;
                }else{
                    if(isset($result['image_blob'])){
                        //下次压缩不使用初始二进制流，使用本次压缩后的二进制流
                        $imageBlobOriginal = $result['image_blob'];
                    }
                    $cutQuality = $this->getCutQuality($result['image_size']);
                    $quality = $quality - $cutQuality;
                }
            }
        }
        if($result['image_size'] > $limitSize){
            $result = [];
        }
        if(!empty($result) && !empty($originalRet)){
            //记录图片原始数据
            $result['image_height_original'] = $originalRet['image_height_original'];
            $result['image_width_original'] = $originalRet['image_width_original'];
            $result['image_size_original'] = $originalRet['image_size_original'];
            $result['image_blob_original'] = $originalRet['image_blob_original'];
            $result['image_base64str_original'] = $originalRet['image_base64str_original'];
        }
        return $result;
    }
    /**
     * 压缩图片v1.0(递归占内存，原因待排查)
     * @param $imageBlobOriginal 图片二进制流
     * @param $limitSize 要求图片占磁盘的字节大小(压缩后的图片尽量接近这个值)
     * @param $limitWidth 要求图片的宽(0--表示仅压缩所占磁盘大小)
     * @param $limitHeight 要求图片的高(0--表示仅压缩所占磁盘大小)
     * @param int $heightMultiplyWidth 限制的长*宽 乘积数(0--表示不限制长宽积的大小)
     * @param int $quality 压缩质量(默认100，待确认)
     * @param int $count 压缩次数，第一次为0
     * @return array
     * @throws \ImagickException
     */
    public function compressImageOld($imageBlobOriginal,$limitSize,$limitWidth=0,$limitHeight=0,$heightMultiplyWidth=0,$quality=80,$count=0){
        try{
//        $pathParts = pathinfo($imageUrl);
//        $imageOriginalName = $pathParts['filename'];//获取图片名称
            //为防止读取远程图片失败，读取其二进制流
//        $imageBlobOriginal = $this->getBlobByImageUrl($imageUrl);
            $imageBase64strOriginal = $this->getBase64ByBlob($imageBlobOriginal);
            $imgObject = new \Imagick();
//            $imgObject->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, MY_MEMORY_LIMIT);
            $imgObject->readImageBlob($imageBlobOriginal);
            $imageTypeOriginal = $imgObject->getImageFormat();

            $widthOriginal = $width = $imgObject->getImageWidth();
            $heightOriginal = $height = $imgObject->getImageHeight();
            $rate = $this->getCompressRate($height,$width,$limitHeight,$limitWidth,$heightMultiplyWidth,$count);
            $imageSizeOriginal = $imgObject->getImageLength();
            if( $rate==$this->rateOriginal && $imageSizeOriginal<$limitSize){
                //所传图片满足要求，无需压缩
                $imgObject->destroy();
                return [
                    'compress' => false,
                    'image_base64str' => $imageBase64strOriginal,
                    'image_blob' => $imageBlobOriginal,
                    'image_height' => $heightOriginal,
                    'image_width' => $widthOriginal,
                    'image_size' => $imageSizeOriginal,
//                'image_name_original' => $imageOriginalName,
                    'image_height_original' => $heightOriginal,
                    'image_width_original' => $widthOriginal,
                    'image_size_original' => $imageSizeOriginal,
                    'image_blob_original' => $imageBlobOriginal,
                    'image_base64str_original' => $imageBase64strOriginal,
                ];
            }
            $width = $rate*$width;
            $height = $rate*$height;
            // 去除图片信息
            $imgObject->stripImage();
//        $imgObject->setImageCompression(\Imagick::COMPRESSION_JPEG);
            /*
            if('png' == strtolower($imageTypeOriginal)){
                $compressionLevel = intval($quality/10);
                $imgObject->setOption('png:compression-level', $compressionLevel);
            }else{
                $imgObject->setImageCompressionQuality($quality);//png不支持此方法,使用
            }
            */
            $imgObject->setImageCompressionQuality($quality);//png不支持此方法,使用
            $imgObject->resizeImage($width,$height,\Imagick::FILTER_LANCZOS,1);
            // 设置压缩比率
            $imgObject->setImageFormat('jpg');
            $blob = $imgObject->getImageBlob();
//        $imageSize = $imgObject->getImageSize();//已弃用，1048576bytes = 1MB，要求图片大小不能超过1MB
            $imageSize = $imgObject->getImageLength();

            $imgObject->destroy();
            return [
                'compress' => true,
                'image_base64str' => $this->getBase64ByBlob($blob),
                'image_blob' => $blob,
                'image_height' => $height,
                'image_width' => $width,
                'image_size' => $imageSize,
//                'image_name_original' => $imageOriginalName,
                'image_height_original' => $heightOriginal,
                'image_width_original' => $widthOriginal,
                'image_size_original' => $imageSizeOriginal,
                'image_blob_original' => $imageBlobOriginal,
                'image_base64str_original' => $imageBase64strOriginal,
            ];
            /*
            if($imageSize < $limitSize){
                return [
                    'compress' => true,
                    'image_base64str' => $this->getBase64ByBlob($blob),
                    'image_blob' => $blob,
                    'image_height' => $height,
                    'image_width' => $width,
                    'image_size' => $imageSize,
//                'image_name_original' => $imageOriginalName,
                    'image_height_original' => $heightOriginal,
                    'image_width_original' => $widthOriginal,
                    'image_size_original' => $imageSizeOriginal,
                    'image_blob_original' => $imageBlobOriginal,
                    'image_base64str_original' => $imageBase64strOriginal,
                ];
            }else{
                $quality = $quality - 2;
                return $this->compressImage($imageBlobOriginal,$limitSize,$limitWidth,$limitHeight,$heightMultiplyWidth,$quality);
            }
            */
        }catch (\Exception $e){
//            var_dump($e->getMessage());
            return [];
        }
    }

    /**
     * 根据所传参数,获取压缩的比率
     * @param $imageHeight 图片高
     * @param $imageWidth 图片宽
     * @param $limitHeight 限制的图片高(0-表示尺寸合适)
     * @param $limitWidth 限制图片宽(0-表示尺寸合适)
     * @param $heightMultiplyWidth 限制的长*宽 乘积数
     * @return mixed
     */
    public function getCompressRate($imageHeight,$imageWidth,$limitHeight,$limitWidth,$heightMultiplyWidth,$count){
        if($limitHeight==0 || $limitWidth==0) {
            //表示尺寸无需压缩，仅压缩所占磁盘大小大小
            $rate = $this->rateOriginal - 0.1*$count;
            return $rate;
        }
        $rate1 = $limitHeight/$imageHeight;
        $rate2 = $limitWidth/$imageWidth;
        if(empty($heightMultiplyWidth)){
            $tmp = $this->rateOriginal;
        }else{
            $tmp = $heightMultiplyWidth/($imageWidth*$imageHeight);
        }
        $rate3 = sqrt($tmp);
        $rate = min($rate1,$rate2,$rate3);
        return $rate;
    }

    /**
     * 根据图片大小获取下次压缩应减少的质量
     * @param $imageSize 图片大小bytes
     * @return int
     */
    public function getCutQuality($imageSize){
        if($imageSize >= 20*$this->sizeTrillion){
            //大于20MB
            return 5;
        }elseif($imageSize >= 15*$this->sizeTrillion){
            //15~20MB
            return 4;
        }elseif($imageSize >= 10*$this->sizeTrillion){
            //10~15MB
            return 3;
        }else{
            //小于10MB
            return 2;
        }
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
     * 通过二进制流 获取 base64str
     * @param $blob
     * @return mixed|string
     */
    public function getBase64ByBlob($blob){
        $base64str = chunk_split(base64_encode($blob));
        $base64str = str_replace("\r\n",'',$base64str);
        $base64str = str_replace("\n",'',$base64str);
        $base64str = str_replace("\r",'',$base64str);
        return $base64str;
    }

    /**
     * 通过base64str 获取 二进制流
     * @param $base64str
     * @return bool|string
     */
    public function getBlobByBase64($base64str){
        $base64str = $this->getBaseImage($base64str);
        return base64_decode($base64str);
    }

    /**
     * 根据图片地址获取其base64编码
     * @param $imageUrl
     * @return string
     */
    public function getBase64ByImageUrl($imageUrl){
        try{
            $data = $this->imageFileGetContents($imageUrl);
            $base64str = base64_encode($data);
        }catch (\Exception $e){
            $base64str = '';
        }
        return $base64str;
    }

    /**
     * 根据图片地址获取其二进制流
     * @param $imageUrl
     * @return false|string
     */
    public function getBlobByImageUrl($imageUrl){
        try{
            $blob = $this->imageFileGetContents($imageUrl);
        }catch (\Exception $e){
            $blob = '';
        }
        return $blob;
    }

    /**
     * file_get_contents 设置https验证&超时时间
     * @param $imageUrl
     * @return false|string
     */
    public function imageFileGetContents($imageUrl){
        try{
            $arrContextOptions=array(
                "ssl"=>array(
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ),
                'http'=>array(
                    'method'=> "GET",
                    'timeout'=> 10,//单位秒
                )
            );
            return file_get_contents($imageUrl, false, stream_context_create($arrContextOptions));
        }catch (\Exception $e){
            return '';
        }
    }


}
