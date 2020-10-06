<?php

/**
 * 日志类
 */

namespace Scpzc\Tools;

class Log
{


    /**
     * 记录日志
     * author: panzhaochao
     * date: 2020-10-06 23:26
     *
     * @param        $content  //日志内容
     * @param string $folderName  //存放文件夹
     *
     * @return bool|false|int
     */
    public static function write($content, $folderName = '')
    {
        if(is_array($content)){
            $content = json_encode($content,JSON_UNESCAPED_UNICODE);
        }
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        $logPath = base_path().'/storage/logs/';
        $logPath .= $folderName;
        if(!is_dir($logPath)) mkdir($logPath, 0644, true);

        $logFile = $logPath . '/'.date('Y-m-d').'.log';

        // 日志记录路径
        $logContent = '----LOG START----'.PHP_EOL;
        $logContent .= 'DATE:' . date("Y-m-d H:i:s") . PHP_EOL;
        $logContent .= 'FILE:' . $backtrace[0]['file'] .' LINE:' . $backtrace[0]['line'] . PHP_EOL;
        $logContent .= 'CONTENT:' . $content . PHP_EOL;
        $logContent .= '-----LOG END-----'.PHP_EOL;
        return file_put_contents($logFile, $logContent.PHP_EOL, FILE_APPEND);
    }


}
