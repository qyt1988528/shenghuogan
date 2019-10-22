<?php
if(isset($_SERVER['HTTP_ORIGIN'])){
	header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Access-Token,RAW-BODY');
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
date_default_timezone_set('UTC');
require __DIR__.'/../vendor/autoload.php';
$app = MDK\Boot::app();
$app->run();