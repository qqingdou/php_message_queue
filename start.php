<?php
/**
 * Created by PhpStorm.
 * User: Alvin Tang
 * Date: 2017/10/31
 * Time: 13:47
 * Author: 442353346@qq.com
 * Desc: PHP消息消费方
 * 调用格式： sudo php start.php [name_space]
 * [name_space] 必须为小写
 * eg:  sudo php start.php gamecenter
 */

$params = $argv;

if(empty($params) || count($params) < 2){
    exit("参数非法。请使用：sudo php start.php [name_space]\n");
}

$name_space = $argv[1];

// Make it possible to test in source directory
// This is for PEAR developers only
ini_set('include_path', ini_get('include_path').':..');

// Include Class
error_reporting(E_ALL);
require_once "System/Daemon.php";

// Bare minimum setup
System_Daemon::setOption("appName", $name_space);
System_Daemon::setOption("usePEAR", false);

//System_Daemon::setOption("appDir", dirname(__FILE__));
System_Daemon::log(System_Daemon::LOG_INFO, "Daemon not yet started so ".
    "this will be written on-screen");

// Spawn Deamon!
System_Daemon::start();
System_Daemon::log(System_Daemon::LOG_INFO, "Daemon: '".
    System_Daemon::getOption("appName").
    "' spawned! This will be written to ".
    System_Daemon::getOption("logLocation"));

// Your normal PHP code goes here. Only the code will run in the background
// so you can close your terminal session, and the application will
// still run.

//业务开始

/*******************配置从此开始*****************/

/******进程数，建议为CPU核数******/
$process_count = 2;
/******redis配置****************/
$redisConfig = array(
    'host' => '127.0.0.1',
    'port' => 6379,
);

/*******************配置从此结束*****************/

define('PM_ROOT', dirname(__FILE__));

require_once 'PhpMessage/PhpMessageConsume.php';

$consume = new PhpMessageConsume($name_space, $process_count);

$consume->redisConfig($redisConfig['host'], $redisConfig['port']);

$consume->start();

System_Daemon::stop();