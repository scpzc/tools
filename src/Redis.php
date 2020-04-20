<?php

namespace Scpzc\Tools;

use Illuminate\Support\Facades\Redis as Predis;

class Redis
{
    public static $redisConnection = null;   //redis连接库

    /**
     * 使用哪个配置
     * author: panzhaochao
     * date: 2020-04-19 22:15
     */
    public static function connection($redisConfig = 'default'){
        self::$redisConnection = Predis::connection($redisConfig);
    }

    /**
     * 设置string类型缓存
     * date: 2019-08-25 19:05
     *
     * @param      $key     //缓存变量名
     * @param      $value   //缓存数据
     * @param bool $expireSeconds  //过期时间（秒）
     *
     * @return mixed
     */
    public static function set($key, $value, $expireSeconds = null)
    {
        // 对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object($value) || is_array($value)) ? json_encode($value,JSON_UNESCAPED_UNICODE) : $value;
        if ($expireSeconds === null) {
            $result = Predis::set($key, $value);
        } else {
            $result = Predis::setex($key, $expireSeconds, $value);
        }
        return $result;
    }

    /**
     * 获取string数据类型缓存
     *
     * @param string $key 缓存变量名
     * @return mixed
     */
    public static function get($key)
    {
        $value = Predis::get($key);
        $jsonValue = json_decode($value,true);
        if(!is_null($jsonValue)){
            $value = $jsonValue;
        }
        return $value;
    }

    /**
     * 获取key的过期时间，单位秒
     *
     * @param string $key 缓存变量名
     * @return mixed
     */
    public static function ttl($key)
    {
        return Predis::ttl($key);
    }


    /**
     * 设置key的过期时间，单位秒
     *
     * @param string $key 缓存变量名
     * @param string $seconds 过期时间，单位秒
     * @return mixed
     */
    public static function expire($key, $expireSeconds)
    {
        return Predis::expire($key, $expireSeconds);
    }



    /**
     * 加锁
     *
     * @param string $key 缓存变量名
     * @param string $value 缓存数据
     * @return mixed
     */
    public static function setnx($key, $value, $expireSeconds = null)
    {
        if($expireSeconds === null){
            $result = Predis::setnx($key, $value);
        }else{
            $result = Predis::set($key, $value, 'ex', $expireSeconds, 'nx');
        }
        return $result;
    }

    /**
     * string数据类型值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
     *
     * @param string $key 缓存变量名
     * @param int $offset 一次性加多少，默认1
     * @return int
     */
    public static function incr($key, $offset = 1, $expireSeconds = null)
    {
        $result = Predis::incrBy($key, $offset);
        if ($expireSeconds !== null) {
            Predis::expire($key, $expireSeconds);
        }
        return $result;
    }

    /**
     * string数据类型值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
     *
     * @param string $key 缓存变量名
     * @param int $offset 一次性减多少,默认1
     * @return int
     */
    public static function decr($key, $offset = 1, $expireSeconds = null)
    {
        $result = Predis::decrBy($key, $offset);
        if ($expireSeconds !== null) {
            Predis::expire($key, $expireSeconds);
        }
        return $result;
    }

    /**
     * 删除string数据类型$key的缓存
     *
     * @param string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return boolean
     */
    public static function del($key)
    {
        return Predis::del($key);
    }




}
