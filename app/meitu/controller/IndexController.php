<?php
namespace Meitu\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/meitu", name="meitu")
 */
class IndexController extends Controller
{

    /**
     * Index action.
     * @return void
     * @Route("/head", methods="POST", name="face")
     */
    public function headReplaceAction(){
//        $userUrl = $this->request->getParam('image_url',null,'');//需要用户url
        $imageType = $this->request->getParam('image_type',null,'');
        $imageBase64 = $this->request->getParam('image_base64',null,'');
        try{
            $msg = $this->translate->_('Network Error. Please Try Again Later!');
            if(empty($imageType) || empty($imageBase64)){//empty($userUrl)
                $this->resultSet->error(1001,$msg);
            }
            $imageBase64 = $this->app->core->api->Image()->getBaseImage($imageBase64);
            $params = [
                "parameter" => [
                    'rsp_media_type' => 'url',
//                    'nType' => 1,//是否加描边，取值范围 0-2，默认值为 1。0 表示不加，1 表示剪纸描边+扣头，2 表示获取头部 mask
//                    'nDistane' => 10,//描边宽度，默认值为 10
//                    'nSigma' => 50,//平滑程度，默认值为 10
                ],
                "media_info_list" => [
                    [
//                        "media_data" => $userUrl,
//                        "media_data" => $userUrl.$this->app->core->config->qiniu->qiniuThumbnailKey;
//                        "media_profiles" => ["media_data_type" => "url"]
                        "media_data" => $imageBase64,
                        "media_profiles" => ["media_data_type" => $imageType]
                    ]
                ],
            ];
            $this->profiler->start('meituHead');
            $data = $this->app->meitu->api->Helper()->headReplace($params);
            $this->profiler->stop('meituHead');
            $outputUrl =$data['media_info_list'][0]['media_data'] ?? '';
            if(empty($outputUrl)){
                $this->resultSet->error(1007,$msg);
            }else{
                $data = [
                    'output_url' => $outputUrl
                ];
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
}
