<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2019/10/27
 * Time: 下午2:59
 */

$data = '{"header":{"serviceName":"\/home"},"error":0,"message":"ok","data":{"cover":"","icon":[{"title":"\u8d85\u5e02","img_url":"","id":1},{"title":"\u517c\u804c","img_url":"","id":2},{"title":"\u95e8\u7968","img_url":"","id":3},{"title":"\u4f4f\u5bbf","img_url":"","id":4},{"title":"\u9910\u996e","img_url":"","id":5},{"title":"\u6821\u56ed\u7f51","img_url":"","id":6},{"title":"\u79df\u623f","img_url":"","id":7},{"title":"\u79df\u8f66","img_url":"","id":8},{"title":"\u4e8c\u624b\u7269","img_url":"","id":9},{"title":"\u5feb\u9012","img_url":"","id":10}],"ad":[{"title":"\u5931\u7269\u62db\u9886","desc":"\u627e\u56de\u60a8\u5931\u53bb\u7684\u7231","id":1},{"title":"\u9a7e\u8003\u62a5\u540d","desc":"\u5168\u7ebf\u4f18\u8d28\u9a7e\u6821","id":2}],"recommend_list":[{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":""},{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":""},{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":""}],"part_time_job_list":[{"title":"\u5feb\u9012","location":"","id":10,"publish_time":"","price":""},{"title":"\u5feb\u9012","location":"","id":10,"publish_time":"","price":""},{"title":"\u5feb\u9012","location":"","id":10,"publish_time":"","price":""}],"life_info_list":[{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":"","publish_time":""},{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":"","publish_time":""},{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":"","publish_time":""}]}}';
$apiData = json_decode($data,true);

//首页
$indexData = [];
//超市(ps:分页)
//兼职
//门票
//住宿
//餐饮
//校园网
//租房
//租车
//二手物
//快递
//广告位
//今日推荐(更多)
//兼职推荐(更多)
//生活信息(更多)
//我的
//我的订单
//我的兼职
//我的租房
//二手物

//商户模式

//订单管理
//商品管理
//商家管理？
//快递管理
//财务管理 账单
//认证管理
exit;