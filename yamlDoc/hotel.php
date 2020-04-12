<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/3/13
 * Time: 下午11:51
 */
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0",
 *     title="Example for response examples value"
 * )
 */
/**
 * @OA\Post(
 *     path="/pet/{petId}",
 *     tags={"pet"},
 *     summary="Updates a pet in the store with form data",
 *     operationId="updatePetWithForm",
 *     @OA\Parameter(
 *         name="petId",
 *         in="path",
 *         description="ID of pet that needs to be updated",
 *         required=true,
 *         @OA\Schema(
 *             type="integer",
 *             format="int64"
 *         )
 *     ),
 *     @OA\Response(
 *         response=405,
 *         description="Invalid input"
 *     ),
 *     security={
 *         {"petstore_auth": {"write:pets", "read:pets"}}
 *     },
 *     @OA\RequestBody(
 *         description="Input data format",
 *         @OA\MediaType(
 *             mediaType="application/x-www-form-urlencoded",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="name",
 *                     description="Updated name of the pet",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="status",
 *                     description="Updated status of the pet",
 *                     type="string"
 *                 )
 *             )
 *         )
 *     )
 * )
 */
/**
 * @OA\Get(
 *     path="/pet/findByStatus",
 *     tags={"pet"},
 *     summary="Finds Pets by status",
 *     description="Multiple status values can be provided with comma separated string",
 *     operationId="findPetsByStatus",
 *     deprecated=true,
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Status values that needed to be considered for filter",
 *         required=true,
 *         explode=true,
 *         @OA\Schema(
 *             type="array",
 *             default="available",
 *             @OA\Items(
 *                 type="string",
 *                 enum = {"available", "pending", "sold"},
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="successful operation",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Pet")
 *         ),
 *         @OA\XmlContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Pet")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid status value"
 *     ),
 *     security={
 *         {"petstore_auth": {"write:pets", "read:pets"}}
 *     }
 * )
 */
