<?php
/**
 * Created by PhpStorm.
 * User: Alvin Tang
 * Date: 2017/10/31
 * Time: 10:45
 * Author: 442353346@qq.com
 * Desc: PHP消息系统消费端
 */
require_once PM_ROOT . '/Core/Log/LogHelper.php';
require_once PM_ROOT . '/Core/Net/HttpClient.php';
require_once PM_ROOT . '/Core/Client/RedisClient.php';

class PhpMessageConsume{

    /**
     * 进程间通信信号
     * @var null
     */
    private $signal = null;

    /**
     * REDIS客户端
     * @var null
     */
    private $redis = null;

    /**
     * 开启进程数，建议为CPU内核数
     * @var int
     */
    private $processCount = 2;

    /**
     * 项目名称
     * @var string
     */
    private $nameSpace = 'gamecenter';

    /**
     * 日志组件
     * @var null
     */
    private $logUtil = null;

    /**
     * PhpMessageConsume constructor.
     * @param string $nameSpace
     * @param int $processCount
     */
    function __construct($nameSpace = 'gamecenter', $processCount = 2){
        $this->checkEnvironment();
        $this->nameSpace = $nameSpace;
        $this->processCount = $processCount;
    }

    /**
     * 初始化
     */
    private function init(){
        $sem_id = ftok(__FILE__, 's');
        $this->signal = sem_get($sem_id);
        $this->logUtil = LogHelper::instance();
        $GLOBALS['childList'] = array();
    }

    /**
     * 验证环境
     */
    private function checkEnvironment(){
        if(php_sapi_name() !== 'cli'){
            trigger_error('必须在命令行模式下运行。', E_USER_ERROR);
        }
        if(!function_exists('posix_getpid')){
            trigger_error('请先开启posix扩展。', E_USER_ERROR);
        }
        if(!function_exists('pcntl_signal')){
            trigger_error('请先开启pcntl扩展。', E_USER_ERROR);
        }
    }

    /**
     * 配置REDIS参数
     * @param string $host
     * @param int $port
     */
    function redisConfig($host = '127.0.0.1', $port = 6379){
        $this->redis = RedisClient::instance($host, $port);
    }

    /**
     * 创建子进程
     */
    private function createChildProcess(){
        $currPid = posix_getpid();

        $this->logUtil->Log("父进程ID: {$currPid}。\n", LogHelper::TAG_INFO);

        for ($i = 0; $i < $this->processCount; $i++) {

            $childPid = $this->createProcess();

            $GLOBALS['childList'][$childPid] = 1;

            $this->logUtil->Log("创建消费的子进程: {$childPid} \n", LogHelper::TAG_INFO);
        }
    }

    /**
     * 消费方
     */
    private function consume(){
        $lastHaveData = true;

        while (true){

            //上次没数据的话，休息一段时间
            if(!$lastHaveData){
                sleep(2);
            }
            //pcntl_signal_dispatch 是每调用此函数一次，才会捕捉并分发一次。可以减少不必要的浪费
            pcntl_signal_dispatch();

            //获得信号量
            sem_acquire($this->signal);

            $data = $this->redis->exists($this->nameSpace) ? $this->redis->rPop($this->nameSpace) : '';

            // 用完释放
            sem_release($this->signal);

            if(!empty($data)){

                $lastHaveData = true;

                //记录取出的数据
                $this->logUtil->Log($data, LogHelper::TAG_INFO);

                $protocol = strtolower(isset($data['protocol']) && !empty($data['protocol']) ? $data['protocol'] : 'http');

                $result = false;

                switch ($protocol){
                    case 'http':
                        if(isset($data['post_data']) && !empty($data['post_data'])){
                            $result = HttpClient::post($data['url'], $data['post_data']);
                        }else{
                            $result = HttpClient::get($data['url']);
                        }
                        break;
                }

                $this->logUtil->Log($result, LogHelper::TAG_INFO);
            }else{
                $lastHaveData = false;
            }
        }
    }

    /**
     * 注册子进程信号处理器
     */
    private function registerChildSig(){
        //使用ticks需要PHP 4.3.0以上版本
        //declare(ticks = 1);
        pcntl_signal(SIGINT, function ($signo){
            $currPid = posix_getpid();

            $data = array(
                'status' => '接收到退出信号',
                'signo' => $signo,
                'curr_pid' => $currPid,
            );

            LogHelper::instance()->Log($data, LogHelper::TAG_INFO);

            exit(1);
        });
    }

    /**
     * 注册父进程信号
     */
    private function registerParentSig(){
        //使用ticks需要PHP 4.3.0以上版本
        //declare(ticks = 1);
        pcntl_signal(SIGINT, function ($signo){
            $logData = array(
                'name' => '父进程信号处理器',
                'signo' => $signo,
                'pid' => posix_getpid(),
                'child_list' => $GLOBALS['childList'],
            );

            LogHelper::instance()->Log($logData, LogHelper::TAG_INFO);

            if(!empty($GLOBALS['childList'])){
                foreach ($GLOBALS['childList'] as $childPid => $value){
                    posix_kill($childPid, SIGINT);
                }
            }
        });
    }

    /**
     * 创建进程
     * @return int
     */
    private function createProcess(){

        $childPid = pcntl_fork();

        if($childPid < 0){
            $this->logUtil->Log("创建子进程出错了.\n", LogHelper::TAG_INFO);
        }else if($childPid == 0){

            $this->registerChildSig();

            $currPid = posix_getpid();

            $this->logUtil->Log("子进程：{$currPid} 开始执行。\n", LogHelper::TAG_INFO);

            $this->consume();

            $this->logUtil->Log("子进程：{$currPid} 执行完毕。\n", LogHelper::TAG_INFO);

            exit(1);

        }else{
            return $childPid;
        }
    }

    /**
     * 父进程监听
     */
    private function parentListen(){
        //等待所有子进程结束
        /*while(!empty($childList)){

            //pcntl_signal_dispatch 是每调用此函数一次，才会捕捉并分发一次。可以减少不必要的浪费
            pcntl_signal_dispatch();

            $status = 0;

            //异步等待进程状态返回
            $childPid = pcntl_wait($status, WNOHANG);

            if ($childPid > 0){

                $logUtil->Log("{$childPid} 执行完毕。\n", LogHelper::TAG_INFO);

                unset($childList[$childPid]);
            }
            //开启等待模式
            sleep(2);
        }*/

        while (true){
            if(empty($GLOBALS['childList'])){
                break;
            }else{

                pcntl_signal_dispatch();

                foreach ($GLOBALS['childList'] as $childPid => $value){
                    if(!posix_kill(intval($childPid), 0)){
                        unset($GLOBALS['childList'][$childPid]);
                    }
                }
                sleep(2);
            }
        }
        $this->logUtil->Log("父进程执行完成。 \n", LogHelper::TAG_INFO);
    }

    /**
     * 启动消费端
     */
    function start(){
        if(empty($this->redis)){
            trigger_error('请先配置REDIS。', E_USER_ERROR);
        }

        $this->init();

        $this->createChildProcess();

        $this->registerParentSig();

        $this->parentListen();

        sem_remove($this->signal);

        $this->logUtil->Log("父进程移除信号。 \n", LogHelper::TAG_INFO);
    }
}