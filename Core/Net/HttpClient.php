<?php

//namespace Core\Net;

/**
 * Created by PhpStorm.
 * User: Alvin Tang
 * Date: 2017/10/18
 * Time: 17:11
 * Author: 442353346@qq.com
 * Desc: HTTP请求客户端
 */
class HttpClient {
    /**
     * 超时时间，单位秒
     */
    const TIME_OUT = 10;

    /**
     * GET请求
     * @param $url
     * @param int $timeout
     * @return bool|mixed
     */
    static function get($url, $timeout = self::TIME_OUT){
        return self::request($url, 'GET', '', $timeout);
    }

    /**
     * POST请求
     * @param $url
     * @param $postData
     * @param int $timeout
     * @return bool|mixed
     */
    static function post($url, $postData, $timeout = self::TIME_OUT){
        return self::request($url, 'POST', $postData, $timeout);
    }

    /**
     * 模拟提交参数，支持https提交 可用于各类api请求, 支持HTTPS
     * @param $url
     * @param string $method
     * @param string $data
     * @param int $timeout
     * @return bool|mixed
     * @throws Exception
     */
    public static function request($url, $method='GET', $data='', $timeout = 10){
        if(!function_exists('curl_init')){
            throw new Exception("curl_init is not exists.");
        }

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        //curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        if($method=='POST'){
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            if ($data != ''){
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
            }
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }
}