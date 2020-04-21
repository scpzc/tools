<?php

namespace Scpzc\Tools;


class RedisCore
{
    private static $connectionPool = []; //存储数据连接对象数组
    private $redis;  //redis连接资源

    private function __construct($connectionName)
    {
        $this->redis =  \Illuminate\Support\Facades\Redis::connection($connectionName);
    }

    /**
     * 使用哪个配置
     * author: panzhaochao
     * date: 2020-04-19 22:15
     */
    public static function connect($connectionName = 'default'){
        // 判断是否已经实例化
        if (!isset(self::$connectionPool[$connectionName])) {
            // 没有实例化过，实例化，并存储
            self::$connectionPool[$connectionName] = new self($connectionName);
        }
        // 返回
        return self::$connectionPool[$connectionName];

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
    public function set($key, $value, $expireSeconds = 3600*24)
    {
        // 对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object($value) || is_array($value)) ? json_encode($value,JSON_UNESCAPED_UNICODE) : $value;
        if ($expireSeconds === null) {
            $result = $this->redis->set($key, $value);
        } else {
            $result = $this->redis->setex($key, $expireSeconds, $value);
        }
        return $result;
    }

    /**
     * 获取string数据类型缓存
     *
     * @param string $key 缓存变量名
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->redis->get($key);
        $jsonValue = json_decode($value,true);
        if(!is_null($jsonValue)){
            $value = $jsonValue;
        }
        return $value;
    }



    /**
     * 加锁
     *
     * @param string $key 缓存变量名
     * @param string $value 缓存数据
     * @return mixed
     */
    public function setnx($key, $value, $expireSeconds = 3600*24)
    {
        if($expireSeconds === null){
            $result = $this->redis->setnx($key, $value);
        }else{
            $result = $this->redis->set($key, $value, 'ex', $expireSeconds, 'nx');
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
    public function incr($key, $offset = 1, $expireSeconds = 3600*24)
    {
        $result = $this->redis->incrby($key, $offset);
        if ($expireSeconds !== null) {
            $this->redis->expire($key, $expireSeconds);
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
    public function decr($key, $offset = 1, $expireSeconds = 3600*24)
    {
        $result = $this->redis->decrby($key, $offset);
        if ($expireSeconds !== null) {
            $this->redis->expire($key, $expireSeconds);
        }
        return $result;
    }



    /**
     * Handle dynamic, calls to the object.
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function __call ($method, $args){
        return $this->redis->$method(...$args);
    }


}