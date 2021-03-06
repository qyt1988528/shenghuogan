<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/3/13
 * Time: 下午11:51
 */
use OpenApi\Annotations as OA;


/**
 * @OA\Post(
 *     path="/addressadmin/create",
 *     tags={"address"},
 *     summary="添加地址",
 *     @OA\RequestBody(
 *         description="添加地址时所需传的数据",
 *         @OA\MediaType(
 *             mediaType="application/x-www-form-urlencoded",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"name","cellphone","province_id","city_id","county_id","detailed_address"},
 *                 @OA\Property(
 *                     property="name",
 *                     description="收件人姓名",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="cellphone",
 *                     description="收件人电话",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="province_id",
 *                     description="省ID",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="city_id",
 *                     description="市ID",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="county_id",
 *                     description="区ID",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="detailed_address",
 *                     description="详细地址",
 *                     type="string"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="successful operation",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="error",
 *                     description="正确返回时为0",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="message",
 *                     description="信息描述",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="data",
 *                     description="返回数据体",
 *                     type="object",
 *                     @OA\Property(
 *                         property="create_result",
 *                         description="创建结果是否成功",
 *                         type="boolean",
 *                     ),
 *                     @OA\Property(
 *                         property="id",
 *                         description="新增的地址ID",
 *                         type="integer",
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/addressadmin/update",
 *     tags={"address"},
 *     summary="更新地址",
 *     @OA\RequestBody(
 *         description="更新地址时所需传的数据",
 *         @OA\MediaType(
 *             mediaType="application/x-www-form-urlencoded",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"id","name","cellphone","province_id","city_id","county_id","detailed_address"},
 *                 @OA\Property(
 *                     property="id",
 *                     description="地址ID",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="name",
 *                     description="收件人姓名",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="cellphone",
 *                     description="收件人电话",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="province_id",
 *                     description="省ID",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="city_id",
 *                     description="市ID",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="county_id",
 *                     description="区ID",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="detailed_address",
 *                     description="详细地址",
 *                     type="string"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="successful operation",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="error",
 *                     description="正确返回时为0",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="message",
 *                     description="信息描述",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="data",
 *                     description="返回数据体",
 *                     type="object",
 *                     @OA\Property(
 *                         property="update_result",
 *                         description="更新结果是否成功",
 *                         type="boolean",
 *                     ),
 *                     @OA\Property(
 *                         property="id",
 *                         description="更新后的新地址ID",
 *                         type="integer",
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/addressadmin/delete",
 *     tags={"address"},
 *     summary="删除地址",
 *     @OA\RequestBody(
 *         description="更新地址时所需传的数据",
 *         @OA\MediaType(
 *             mediaType="application/x-www-form-urlencoded",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"id"},
 *                 @OA\Property(
 *                     property="id",
 *                     description="地址ID",
 *                     type="integer",
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="successful operation",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="error",
 *                     description="正确返回时为0",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="message",
 *                     description="信息描述",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="data",
 *                     description="返回数据体",
 *                     type="object",
 *                     @OA\Property(
 *                         property="delete_result",
 *                         description="删除结果是否成功",
 *                         type="boolean",
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */



/**
 * @OA\Get(
 *     path="/address/provinceList",
 *     tags={"address"},
 *     summary="省份-列表",
 *     @OA\Response(
 *         response=200,
 *         description="successful operation",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="error",
 *                     description="正确返回时为0",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="message",
 *                     description="信息描述",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="data",
 *                     description="返回数据体",
 *                     type="object",
 *                     @OA\Property(
 *                         property="province_list",
 *                         description="省份列表",
 *                         type="array",
 *                         @OA\Items(
 *                              description="省份具体参数值",
 *                              type="object",
 *                              @OA\Property(
 *                                  property="id",
 *                                  description="省id",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="name",
 *                                  description="省份名称",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="pid",
 *                                  description="省的父级id",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="province_id",
 *                                  description="省id",
 *                                  type="string",
 *                              ),
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/address/cityList",
 *     tags={"address"},
 *     summary="市-列表",
 *     @OA\Parameter(
 *         name="id",
 *         in="query",
 *         description="省份id",
 *         required=true,
 *         @OA\Schema(
 *             type="integer",
 *             default="110000",
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="successful operation",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="error",
 *                     description="正确返回时为0",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="message",
 *                     description="信息描述",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="data",
 *                     description="返回数据体",
 *                     type="object",
 *                     @OA\Property(
 *                         property="city_list",
 *                         description="市列表",
 *                         type="array",
 *                         @OA\Items(
 *                              description="市具体参数值",
 *                              type="object",
 *                              @OA\Property(
 *                                  property="id",
 *                                  description="市id",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="name",
 *                                  description="市名称",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="pid",
 *                                  description="市的父级id",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="city_id",
 *                                  description="市id",
 *                                  type="string",
 *                              ),
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/address/countyList",
 *     tags={"address"},
 *     summary="区-列表",
 *     @OA\Parameter(
 *         name="id",
 *         in="query",
 *         description="市id",
 *         required=true,
 *         @OA\Schema(
 *             type="integer",
 *             default="110100",
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="successful operation",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="error",
 *                     description="正确返回时为0",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="message",
 *                     description="信息描述",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="data",
 *                     description="返回数据体",
 *                     type="object",
 *                     @OA\Property(
 *                         property="county_list",
 *                         description="区列表",
 *                         type="array",
 *                         @OA\Items(
 *                              description="区具体参数值",
 *                              type="object",
 *                              @OA\Property(
 *                                  property="id",
 *                                  description="区id",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="name",
 *                                  description="区名称",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="pid",
 *                                  description="区的父级id",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="county_id",
 *                                  description="区id",
 *                                  type="string",
 *                              ),
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */


/**
 * @OA\Get(
 *     path="/addressadmin/list",
 *     tags={"address"},
 *     summary="当前用户已保存过的地址列表",
 *     @OA\Response(
 *         response=200,
 *         description="successful operation",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="error",
 *                     description="正确返回时为0",
 *                     type="integer",
 *                 ),
 *                 @OA\Property(
 *                     property="message",
 *                     description="信息描述",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="data",
 *                     description="返回数据体",
 *                     type="object",
 *                     @OA\Property(
 *                         property="address_list",
 *                         description="省份列表",
 *                         type="array",
 *                         @OA\Items(
 *                              description="省份具体参数值",
 *                              type="object",
 *                              @OA\Property(
 *                                  property="id",
 *                                  description="省id",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="name",
 *                                  description="省份名称",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="pid",
 *                                  description="省的父级id",
 *                                  type="string",
 *                              ),
 *                              @OA\Property(
 *                                  property="province_id",
 *                                  description="省id",
 *                                  type="string",
 *                              ),
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */

