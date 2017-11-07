<?php
/**
 * Created by PhpStorm.
 * User: Alvin Tang
 * Date: 2017/10/23
 * Time: 16:59
 * Author: 442353346@qq.com
 * Desc: 服务文件,用来检测状态和停止服务
 * 格式：sudo php service.php [name_space] [status|stop]
 * eg:  sudo php service.php gamecenter stop 停止服务
 * eg:  sudo php service.php gamecenter status 查看服务
 */

if(empty($argv)){
    exit("please input appName and service.\n");
}

#format: php service.php gamecenter status | stop
if(count($argv) < 3){
    exit("please use: php service.php [appName] [status|stop]\n");
}

$appName = trim($argv[1]);
$service = strtolower(trim($argv[2]));

require_once "System/Daemon.php";                 // Include the Class

System_Daemon::setOption("appName", $appName);  // Minimum configuration

//父进程ID
$parentPidFile = System_Daemon::getOption('appPidLocation');

$isRunning = isRunning();

if($isRunning){
    $parentPid = file_get_contents($parentPidFile);
}

switch ($service){
    case 'status':
        if($isRunning){
            exit("{$appName} is running.\n parent pid file: {$parentPid}. \n");
        }else{
            exit("{$appName} is not running.\n");
        }
        break;
    case 'stop':
        if($isRunning){
            posix_kill(intval($parentPid), SIGINT);
            exit("信号发送成功。\n");
        }else{
            exit("{$appName} is not running.\n");
        }
        break;
    default:
        exit("invalid cmd.\n");
        break;
}

/**
 * 是否正在运行
 * @return bool
 */
function isRunning(){
    return System_Daemon::isRunning();
}
