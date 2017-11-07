<?php

//namespace Core\Log;

/**
 * Created by PhpStorm.
 * User: Alvin Tang
 * Date: 2017/10/18
 * Time: 17:29
 * Author: 442353346@qq.com
 * Desc：日志类
 */
class LogHelper{
    /**
     * 错误
     */
    const TAG_ERROR = 'ERROR';
    /**
     * 信息
     */
    const TAG_INFO = 'INFO';
    /**
     * 通知
     */
    const TAG_NOTICE = 'NOTICE';
    /**
     * 日志目录
     * @var string
     */
    public $dir = '';

    /**
     * @param $dir
     * @return LogHelper
     */
    static function instance($dir = ''){
        return new self($dir);
    }
    /**
     * 初始化
     * LogHelper constructor.
     * @param string $dir
     */
    function __construct($dir = ''){
        $this->dir = empty($dir) ? implode(DIRECTORY_SEPARATOR, array(PM_ROOT, 'Core', 'Log', 'Data', date('Y-m-d'))) : $dir;
        $pathArr = explode(DIRECTORY_SEPARATOR, $this->dir);

        $existsDir = array();

        foreach ($pathArr as $item){

            if(empty($item)){
                continue;
            }

            array_push($existsDir, $item);

            $tempDir = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $existsDir);

            if(!is_dir($tempDir)){
                @mkdir($tempDir, 777);
            }
        }

    }

    /**
     * 记录日志
     * @param $content
     * @param string $tag
     */
    public function Log($content, $tag = self::TAG_ERROR){
        $content = is_string($content) ? $content : json_encode($content);
        $writeData = array();
        array_push($writeData, "{$tag} " . date('Y-m-d H:i:s') . "\n");
        array_push($writeData, "{$content} \n\n");

        $fileName = date('Y-m-d'). '.log';
        $fileHandle = fopen($this->dir . DIRECTORY_SEPARATOR . $fileName, 'a+');
        fwrite($fileHandle, implode('', $writeData));
        fclose($fileHandle);
    }
}