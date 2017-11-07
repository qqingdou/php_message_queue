<?php
/**
 * Created by PhpStorm.
 * User: Alvin Tang
 * Date: 2017/10/27
 * Time: 17:13
 * Author: 442353346@qq.com
 * Desc: PHP消息帮助类(依赖REDIS)
 */
class PhpMessageProduce{
    /**
     * REDIS客户端数组
     * @var array
     */
    private $redisClient = array();
    /**
     * REDIS主机地址
     * @var string
     */
    public $redisHost = '127.0.0.1';
    /**
     * REDIS端口
     * @var string
     */
    public $redisPort = '6379';

    /**
     * 构造函数
     * PhpMessageHelper constructor.
     * @param string $redisHost
     * @param string $redisPort
     */
    function __construct($redisHost = '', $redisPort = ''){
        $this->redisHost = empty($redisHost) ? $this->redisHost : $redisHost;
        $this->redisPort = empty($redisPort) ? $this->redisPort : $redisPort;

        $key = "{$this->redisHost}_{$this->redisPort}";
        if(empty($this->redisClient) || !array_key_exists($key, $this->redisClient)){
            $myRedis = new Redis();
            $myRedis->pconnect($this->redisHost, $this->redisPort, 3);
            $this->redisClient[$key] = $myRedis;
        }
    }

    /**
     * 获取实例
     * @param string $redisHost
     * @param string $redisPort
     * @return PhpMessageProduce
     */
    static function instance($redisHost = '', $redisPort = ''){
        return new self($redisHost, $redisPort);
    }
    /**
     * 获取REDIS
     * @return mixed
     */
    private function getRedis(){
        $key = "{$this->redisHost}_{$this->redisPort}";
        return $this->redisClient[$key];
    }

    /**
     * 插入消息
     * 目前只支持http协议
     *      格式JSON如下：
     *          protocol:可为空，string,默认为http
     *          url:必填，string,请求地址
     *          post_data：为POST形式时必填，string,POST数据，如：userid=123&time=2423434
     * @param $nameSpace
     * @param $data
     * @return mixed
     */
    function push($nameSpace, $data){
        return $this->lPush($nameSpace, $data);
    }
    /**
     * 左入队列
     * @param $key
     * @param $value
     * @return mixed
     */
    private function lPush($key, $value){
        return $this->getRedis()->lPush($key, $this->encode($value));
    }

    /**
     * 右出队列
     * @param $key
     * @return mixed
     */
    private function rPop($key){
        return $this->decode($this->getRedis()->rPop($key));
    }

    /**
     * 编码
     * @param $data
     * @return string
     */
    private function encode($data){
        return json_encode($data);
    }

    /**
     * 解码
     * @param $data
     * @return mixed
     */
    private function decode($data){
        return json_decode($data, true);
    }
}

/*
// demo使用方法
$mt = new PhpMessageProduce();
$mt->push('gamecenter', array('url' => 'http://bbs1020.testgc.firedg.com/api/l2/topicCircle'));
*/

