<?php
namespace Core\Controller;
use MDK\Controller;


/**
 * Image controller.
 * @RoutePrefix("/image", name="image")
 */
class ImageController extends Controller
{
    /**
     * 图片压缩
     * @return void
     * @Route("/compress", methods="POST", name="image")
     */
    public function compressAction(){
        $image = $this->request->getParam('image');
        $limitHeight = $this->request->getParam('height',null,0);
        $limitWidth = $this->request->getParam('width',null,0);
        $limitSize = $this->request->getParam('size',null,1048576);//默认1MB
        $product = $this->request->getParam('product',null,0);

        try{
            $imageBlobOriginal = $this->app->core->api->Image()->getBlobByBase64($image);
            $data = $this->app->core->api->Image()->compressImage($imageBlobOriginal,$limitSize,$limitWidth,$limitHeight,$product);
            $data =[
               'image' => $data['image_base64str']?? ''
            ];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
     * 图片处理
     * 原图---图片压缩至满足腾讯滤镜及百度无损放大要求---腾讯滤镜---百度无损放大---图片压缩至与原图宽高一致---上传至七牛---返回七牛URL
     * @return void
     * @Route("/dealImage", methods="POST", name="image")
     */
    public function dealImageAction(){
        $image = $this->request->getParam('image');//图片base64str
        $filter = $this->request->getParam('filter');
//        $limitHeight = $this->request->getParam('height',null,0);
//        $limitWidth = $this->request->getParam('width',null,0);

        try{
            //压缩图片
            $imageBlobOriginal = $this->app->core->api->Image()->getBlobByBase64($image);
            $limitSize = $this->app->core->api->Image()->sizeTrillion;
            $limitWidth = $limitHeight = 1080;
            $product = 800 * 800;
            $compressRet = $this->app->core->api->Image()->compressImage($imageBlobOriginal,$limitSize,$limitWidth,$limitHeight,$product);
            if(empty($compressRet)){
                $this->resultSet->error(1001,'compress image failed');
            }else{
                //腾讯滤镜 ，尺寸长宽不超过1080、原图大小上限1MB
                $imageBase64str = $this->app->core->api->Image()->getWebBaseImage($compressRet['image_base64str']);
                $filterRet = $this->app->tencent->api->Helper()->imgfilter($imageBase64str,$filter);
                //百度无损放大 注意：图片大小不超过4M。长宽乘积不超过800p x 800px。图片的base64编码是不包含图片头的，如（data:image/jpg;base64,）
                $imageBase64strWithoutHead = $this->app->core->api->Image()->getBaseImage($filterRet['image']);
                $data = $this->app->baidu->api->Helper()->imageQualityEnhance($imageBase64strWithoutHead);
                //根据工厂要求拉伸或缩小长宽,暂定原图尺寸
                $imageBlobOriginal = $this->app->core->api->Image()->getBlobByBase64($data['image']);
                $limitSize = 50*$limitSize;
                $limitWidth = $compressRet['image_width_original'];
                $limitHeight = $compressRet['image_height_original'];
                $compressLastRet = $this->app->core->api->Image()->compressImage($imageBlobOriginal,$limitSize,$limitWidth,$limitHeight);
                if(empty($compressLastRet)){
                    $this->resultSet->error(1002,'before upload to qiniu image is error');
                }
//            $data['image'] = $compressLastRet['image_base64str'];
                //上传二进制 至七牛 返回图片地址
                $imageName = 'ai/deal/'.date('Ymd').'/'.$filter.'_'.time().'.jpg';
                $qiniuUploadRet = $this->app->admin->core->api->Qiniu()->uploadBlobToQiniu($compressLastRet['image_blob'],$imageName);
                if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
                    $imgLink = $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url'];
                }else{
                    $this->resultSet->error(1003,'upload to qiniu failed');
                }
            }
            $data = [
                'image_url' => $imgLink
            ];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }
    /**
     * 图片模糊识别
     * 图片压缩至满足腾讯模糊识别要求-腾讯模糊识别
     * @return void
     * @Route("/compressFuzzy", methods="POST", name="image")
     */
    public function compressFuzzyAction(){
        $image = $this->request->getParam('image');//图片base64str
        try{
            //压缩图片
            $imageBlobOriginal = $this->app->core->api->Image()->getBlobByBase64($image);
            $limitSize = $this->app->core->api->Image()->sizeTrillion;
            $compressRet = $this->app->core->api->Image()->compressImage($imageBlobOriginal,$limitSize);
            if(!empty($compressRet)){
                //调腾讯模板识别api，要求图片小于1MB
                $data = $this->app->tencent->api->Helper()->isFuzzy($image);
            }else{
                //压缩失败
                $this->resultSet->error(1001,'compress image failed');
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }
}