<?php
/**
 * Redis改进类
 * author: panzhaochao
 * date: 2020-08-06 11:53
 */

namespace Scpzc\Tools;

use Scpzc\Tools\Core\RedisCore;

/**
 * @method static  RedisCore get(string $key)  获取string数据类型缓存
 * @method static  RedisCore set($key, $value, $expireSeconds)  设置string类型缓存
 * @method static  RedisCore setnx($key, $value, $expireSeconds)  设置值，不存在才成功
 * @method static  RedisCore incr($key, $increment = 1, $expireSeconds = 3600*24)  string数据类型值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
 * @method static  RedisCore decr($key, $increment = 1, $expireSeconds = 3600*24)  string数据类型值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
 */
class Redis
{

    private static $connectPool = []; //存储数据连接对象数组


    /**
     * 使用哪个配置
     * author: panzhaochao
     * date: 2020-04-19 22:15
     */
    public static function connect($connectName = 'default'){
        // 判断是否已经实例化
        if (!isset(self::$connectPool[$connectName])) {
            // 没有实例化过，实例化，并存储
            self::$connectPool[$connectName] = new RedisCore($connectName);
        }
        // 返回
        return self::$connectPool[$connectName];

    }




    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        return self::connect()->$method(...$args);
    }




}
