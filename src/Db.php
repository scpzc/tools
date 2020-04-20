<?php
/**
 * 数据库操作类
 * Created by PhpStorm.
 * Date: 2019/10/26
 * Time: 15:55
 */

namespace Scpzc\Tools;


/**
 * @method static mixed fetchAll($sql='',$params=[])
 * @method static mixed fetchRow($sql = '',$params = [])
 * @method static DbCore connect(string $connectName = null)
 * @method static DbCore table(string $query)
 */

class Db
{

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
        if($method == 'connect'){
            return DbCore::connect(...$args);
        }else{
            $db = DbCore::connect();
            return $db->$method(...$args);
        }
    }


}
