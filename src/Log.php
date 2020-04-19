<?php

/**
 * 日志类
 */

namespace Scpzc\Tools;

use Scpzc\Tools\Db;


class Log
{


    /**
     * 记录日志
     * @param $content
     * @param string $type
     * @author panzhaochao
     * @date 2019-03-24 10:29
     */
    public static function write($content, $type = 'exception')
    {
        if(empty($content)){
            return false;
        }
        if(is_array($content)){
            $content = json_encode($content,JSON_UNESCAPED_UNICODE);
        }
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        //异常日志
        if ($type == 'exception') {
            $logContent = '文件:' . $backtrace[0]['file'] . '<br/>';
            $logContent .= '行数:' . $backtrace[0]['line'] . '<br/>';
            $logContent .= '参数：POST:' . json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $logContent .= 'GET:' . json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $logContent .= 'PARAMS:' . json_encode($GLOBALS['params']??[], JSON_UNESCAPED_UNICODE);
            $logContent .= 'STREAM:' . file_get_contents('php://input') . '<br/>';
            $logContent .= '异常信息:' . $content;
            $data = [
                'type' => $type,
                'content' => $logContent,
            ];
            if(env('APP_ENV') == 'production'){
                \Illuminate\Support\Facades\Redis::rpush(config('redisKey.exception_list'),json_encode($data,JSON_UNESCAPED_UNICODE));
            }
        } else {
            // 日志记录路径
            $logContent = '【DATE:' . date("Y-m-d H:i:s") . '---';
            $logContent .= 'FILE:' . $backtrace[0]['file'] . '---';
            $logContent .= 'LINE:' . $backtrace[0]['line'] . '---';
            $logContent .= 'PARAMS:POST:' . json_encode($_POST, JSON_UNESCAPED_UNICODE) . '---';
            $logContent .= 'GET:' . json_encode($_GET, JSON_UNESCAPED_UNICODE) . '---';
            $logContent .= 'PARAMS:' . json_encode($GLOBALS['params']??[], JSON_UNESCAPED_UNICODE).'---';
            $logContent .= 'STREAM:' . file_get_contents('php://input') . '---';
            $logContent .= 'TYPE:' . $type . '---';
            $logContent .= 'MESSAGE:' . $content . '】';
            \Illuminate\Support\Facades\Log::info($logContent);
        }
    }


}
