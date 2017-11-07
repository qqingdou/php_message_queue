<?php

//namespace Core\Client;

/**
 * Created by PhpStorm.
 * User: Alvin Tang
 * Date: 2017/10/13
 * Time: 15:13
 * Author: 442353346@qq.com
 * Desc: REDIS 帮助类
 */
class RedisClient{
    /**
     * 默认主机
     * @var string
     */
    public $host = '127.0.0.1';
    /**
     * 默认端口
     * @var int
     */
    public $port = 6379;

    private $redisClient = array();

    /**
     * 构造函数
     * RedisClient constructor.
     * @param string $host
     * @param string $port
     * @param int $time
     */
    function __construct($host = '', $port = '', $time = 10){
        $this->host = empty($host) ? $this->host : $host;
        $this->port = empty($port) ? $this->port : $port;

        $key = "{$this->host}_{$this->port}";
        if(empty($this->redisClient) || !array_key_exists($key, $this->redisClient)){
            $myRedis = new Redis();
            $myRedis->pconnect($this->host, $this->port, $time);
            $this->redisClient[$key] = $myRedis;
        }
    }

    /**
     * 获取REDIS
     * @return mixed
     */
    private function getRedis(){
        $key = "{$this->host}_{$this->port}";
        return $this->redisClient[$key];
    }
    /**
     * 获取实例
     * @param string $host
     * @param string $port
     * @return RedisClient
     */
    static function instance($host = '', $port = ''){
        return new self($host, $port);
    }

    /**
     * 左入队列
     * @param $key
     * @param $value
     * @return mixed
     */
    function lPush($key, $value){
        return $this->getRedis()->lPush($key, $this->encode($value));
    }

    /**
     * 右出队列
     * @param $key
     * @return mixed
     */
    function rPop($key){
        return $this->decode($this->getRedis()->rPop($key));
    }

    /**
     * 检测该键值是否存在
     * @param $key
     * @return mixed
     */
    function exists($key){
        return $this->getRedis()->exists($key);
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