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
    public static function write($content, $type = 'info')
    {
        if(empty($content)){
            return false;
        }
        if(is_array($content)){
            $content = json_encode($content,JSON_UNESCAPED_UNICODE);
        }
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        // 日志记录路径
        $logContent = '【DATE:' . date("Y-m-d H:i:s") . '---';
        $logContent .= 'FILE:' . $backtrace[0]['file'] . '---';
        $logContent .= 'LINE:' . $backtrace[0]['line'] . '---';
        $logContent .= 'PARAMS:' . json_encode(request()->all(), JSON_UNESCAPED_UNICODE) . '---';
        $logContent .= 'TYPE:' . $type . '---';
        $logContent .= 'MESSAGE:' . $content . '】';
        \Illuminate\Support\Facades\Log::info($logContent);
    }


}
