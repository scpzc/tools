<?php
/**
 * 数据库操作类
 * Created by PhpStorm.
 * User: panzhaochao
 * Date: 2019/10/26
 * Time: 15:55
 */

namespace App\Extend;

class Db
{
    private static $connectionPool = []; //存储数据连接对象数组
    private $db;  //数据库连接资源
    private $container = [
        'field'       => ' * ',   //查询的字段
        'where'       => '',    //查询条件
        'limit'       => '',    //查询限制
        'update_data' => '',//更新数据
        'insert_data' => '',//插入数据
        'order_by'    => '',   //排序
    ];
    private $sql = '';//执行sql语句
    private $params = [];//绑定的参数
    private $sqlParams = []; //执行过的SQL语句和参数

    private function __construct($connectionName)
    {
        $this->db = \Illuminate\Support\Facades\DB::connection($connectionName);
    }



    /**
     * 进行一个连接
     * author: panzhaochao
     * date: 2019/5/21 21:00
     *
     * @param string $connectionName
     *
     * @return mixed
     */
    public static function connect($connectionName = '')
    {
        // 默认连接名称
        $defaultConnectionName = config('database.default', 'mysql');
        // 连接名称
        $connectionName = !empty($connectionName)?$connectionName:$defaultConnectionName;
        // 判断是否已经实例化
        if (!isset(self::$connectionPool[$connectionName])) {
            // 没有实例化过，实例化，并存储
            self::$connectionPool[$connectionName] = new self($connectionName);
        }
        // 返回
        return self::$connectionPool[$connectionName];
    }

    /**
     * 重置参数
     * author: panzhaochao
     * date: 2019-10-30 18:06
     */
    private function resetParams(){
        $this->container = [
            'field'       => ' * ',   //查询的字段
            'where'       => '',    //查询条件
            'limit'       => '',    //查询限制
            'update_data' => '',//更新数据
            'insert_data' => '',//插入数据
            'order_by'    => '',   //排序
        ];
        $this->params = [];
    }


    /**
     * 设置表名
     * author: panzhaochao
     * date: 2019/5/21 21:13
     *
     * @param $table
     *
     * @return $this
     */
    private function table($table){
        $this->container['table'] = ' `'.$table.'` ';
        return $this;
    }

    /**
     * 设置要查询的字段
     * author: panzhaochao
     * date: 2019/5/21 21:13
     *
     * @param string $field
     *
     * @return $this
     */
    public function field($field = ' * '){
        if(is_array($field)){
            $field = ' '.join(',',$field).' ';
        }
        $this->container['field'] = ' '.$field.' ';
        return $this;
    }



    /**
     * where条件
     * author: panzhaochao
     * date: 2019/5/21 21:13
     *
     * @param $where
     *
     * @return $this
     */
    public function where($where,$params = []){
        if(empty($where)) return $this;
        if(is_string($where)){   //字符串
            $this->container['where'] = ' WHERE '.$where;
            $this->params = array_merge($this->params,$params);
        }elseif(is_array($where)){  //数组
            $whereTemp = [];
            $count = 1;
            foreach($where as $key=>$whereItem){
                if(is_array($whereItem)){        //[['id','=',1]]
                    $whereCount = count($whereItem);
                    if($whereCount == 3){
                        $sign = trim(strtoupper($whereItem[1]));
                        if(in_array($sign,['IN','NOT IN'])){    //[['id','in',[1,2]]]
                            if(!is_array($whereItem[2]))  throw new \Exception('in值应该传一个数组');
                            if(!empty($whereItem[2])){
                                $keysTemp = array_keys($whereItem[2]);
                                $valuesTemp = array_values($whereItem[2]);
                                $inArr = array_map(function($v) use ($whereItem,$count) {return ':'.$whereItem[0].$count.'_'.$v;}, $keysTemp);
                                $whereTemp[] = $whereItem[0].' '.$sign.' ('.join(',',$inArr).')';
                                $this->params = array_merge($this->params,array_combine($inArr,$valuesTemp));
                            }else{
                                $whereTemp[] = $whereItem[0].' '.$sign.' ( null )';
                            }
                        }else{   //[['id','>','121']]等
                            $whereTemp[] = $whereItem[0].' '.$sign.' '.':'.$whereItem[0].$count;
                            $this->params[$whereItem[0].$count] = $whereItem[2];
                        }
                    }elseif($whereCount == 1){
                        foreach($whereItem as $key2=>$whereItem2){      //[['id'=>1]]
                            $whereTemp[] = $key2.' = :'.$key2.$count;
                            $this->params[$key2.$count] = $whereItem2;
                        }
                    }else{
                        throw new \Exception('where的写法有误');
                    }
                }else{     //['id'=>1]
                    $whereTemp[] = $key.' = :'.$key.$count;
                    $this->params[$key.$count] = $whereItem;
                }
                $count++;
            }
            if(!empty($whereTemp)){
                $this->container['where'] = ' WHERE '.join(' AND ',$whereTemp);
            }
        }
        return $this;
    }



    /**
     * 排序
     * author: panzhaochao
     * date: 2019/5/21 22:17
     *
     * @param $orderBy
     *
     * @return $this
     */
    public function order($orderBy){
        $sort = [];
        if(is_array($orderBy)){
            foreach($orderBy as $key=>$val){
                $sort[] = $key.' '.$val;
            }
            $this->container['order_by'] = ' ORDER BY '.join(',',$sort).' ';
        }elseif(is_string($orderBy)){
            $this->container['order_by'] = ' ORDER BY '.$orderBy.' ';
        }
        return $this;
    }

    /**
     * 查询偏量
     * author: panzhaochao
     * date: 2019/5/21 22:19
     *
     * @param $limit
     *
     * @return $this
     */
    public function limit(int $offset = 0, int $limit = 0){
        if(!empty($offset) && empty($limit)){
            $this->container['limit'] = ' LIMIT '.$offset;
        }else{
            $this->container['limit'] = ' LIMIT '.$offset.','.$limit;
        }
        return $this;
    }

    /**
     * 要更新或新增的数据
     * author: panzhaochao
     * date: 2019/5/21 21:58
     *
     * @param $data array
     *
     * @return $this
     */
    private function data($data){
        if(empty($data)) return $this;   //没有数据
        $set = [];    //更新操作
        $insertFields = [];   //添加操作
        foreach($data as $key=>$val){
            if(is_array($val)){
                $count = count($val);
                if($count == 3){   // [['ver','incr',1]]
                    if($val[1] == 'incr'){   // [['ver','incr',1]]
                        $set[] = $val[0].'='.$val[0].'+:data_'.$key.'_'.$val[0];
                        $this->params['data_'.$key.'_'.$val[0]] = $val[2];
                    }elseif($val[1] == 'decr'){   // [['ver','decr',1]]
                        $set[] = $val[0].'='.$val[0].'-:data_'.$key.'_'.$val[0];
                        $this->params['data_'.$key.'_'.$val[0]] = $val[2];
                    }
                }elseif($count == 1){   //[['ver'=>1]]
                    $keysArr = array_keys($val);
                    $field = array_pop($keysArr);
                    $val = array_pop($val);
                    $set[] = $field.'=:'.'data_'.$field;
                    $insertFields[] = $field;
                    $this->params['data_'.$field] = $val;
                }else{
                    throw new \Exception('data格式错误1');
                }
            }else{
                $set[] = $key.'=:'.'data_'.$key;
                $insertFields[] = $key;
                $this->params['data_'.$key] = $val;
            }
        }
        $this->container['insert_data'] = ' ( `'.join('`,`',$insertFields).'` ) VALUES (:data_'.join(',:data_',$insertFields).')';
        $this->container['update_data'] = ' SET '.join(',',$set);
        return $this;
    }

    /**
     * 获取单个值，可以使用原生
     * author: panzhaochao
     * date: 2019/5/21 22:41
     *
     * @param string $sql
     * @param        $params
     *
     * @return mixed
     */
    public function fetchOne($sql = '',$params = []){
        if(!empty($sql)){
            if (
                strpos(trim(strtolower($sql)), 'insert') === 0 ||
                strpos(trim(strtolower($sql)), 'update') === 0 ||
                strpos(trim(strtolower($sql)), 'delete') === 0
            ) {
                throw new \Exception('请使用execute');
            }
            $this->sqlParams[] = ['sql'=>$sql,'params'=>$params];
            $result = $this->db->selectOne($sql,$params);
        }else{
            $this->sql = 'SELECT'.$this->container['field'].'FROM'.$this->container['table'].$this->container['where'].$this->container['order_by'].' LIMIT 1';
            $this->sqlParams[] = ['sql'=>$this->sql,'params'=>$this->params];
            $result = $this->db->selectOne($this->sql,$this->params);
        }
        $this->resetParams();
        $result = $this->objectToArray($result);
        $result = is_array($result)?array_pop($result):null;
        return $result;
    }

    /**
     * 查询一条记录，可以使用原生
     * author: panzhaochao
     * date: 2019/5/21 21:13
     *
     * @return mixed
     */
    private  function fetchRow($sql = '',$params = []){
        if(!empty($sql)){
            if (
                strpos(trim(strtolower($sql)), 'insert') === 0 ||
                strpos(trim(strtolower($sql)), 'update') === 0 ||
                strpos(trim(strtolower($sql)), 'delete') === 0
            ) {
                throw new \Exception('请使用execute');
            }
            $this->sqlParams[] = ['sql'=>$sql,'params'=>$params];
            $result = $this->db->selectOne($sql,$params);
        }else{
            $this->sql = 'SELECT'.$this->container['field'].'FROM'.$this->container['table'].$this->container['where'].$this->container['order_by'].' LIMIT 1';
            $this->sqlParams[] = ['sql'=>$this->sql,'params'=>$this->params];
            $result = $this->db->selectOne($this->sql,$this->params);
        }
        $this->resetParams();
        $result = $this->objectToArray($result);
        return $result;
    }



    /**
     * 查询符合条件的所有记录，可以使用原生
     * author: panzhaochao
     * date: 2019/5/21 22:01
     *
     * @return mixed
     */
    private function fetchAll($sql='',$params=[]){
        if(!empty($sql)){
            if (
                strpos(trim(strtolower($sql)), 'insert') === 0 ||
                strpos(trim(strtolower($sql)), 'update') === 0 ||
                strpos(trim(strtolower($sql)), 'delete') === 0
            ) {
                throw new \Exception('请使用execute');
            }
            $this->sqlParams[] = ['sql'=>$sql,'params'=>$params];
            $result = $this->execute($sql,$params);
        }else{
            $this->sql = 'SELECT '.$this->container['field'].' FROM '.$this->container['table'].$this->container['where'].$this->container['order_by'].$this->container['limit'];
            $this->sqlParams[] = ['sql'=>$this->sql,'params'=>$this->params];
            $result = $this->execute($this->sql,$this->params);
        }
        $this->resetParams();
        return $result;
    }

    /**
     * 获取总数
     * author: panzhaochao
     * date: 2019/5/21 22:41
     *
     * @param string $sql
     * @param        $params
     *
     * @return mixed
     */
    private function count($sql = '',$params = []){
        if(!empty($sql)){
            if (
                strpos(trim(strtolower($sql)), 'insert') === 0 ||
                strpos(trim(strtolower($sql)), 'update') === 0 ||
                strpos(trim(strtolower($sql)), 'delete') === 0
            ) {
                throw new \Exception('请使用execute');
            }
            $this->sqlParams[] = ['sql'=>$sql,'params'=>$params];
            $result = $this->db->selectOne($sql,$params);
        }else{
            $this->sql = 'SELECT count(*) FROM'.$this->container['table'].$this->container['where'].' LIMIT 1';
            $this->sqlParams[] = ['sql'=>$this->sql,'params'=>$this->params];
            $result = $this->db->selectOne($this->sql,$this->params);
        }
        $this->resetParams();
        $result = $this->objectToArray($result);
        $result = array_pop($result);
        return $result;
    }

    /**
     * 分页查询
     * author: panzhaochao
     * date: 2019/5/21 22:30
     *
     * @param array  $pageSize
     *
     * @return mixed
     */
    public function page($pageSize){
        $result = $this->queryBuild()->paginate($pageSize);
        unset($this->container);
        return $this->objectToArray($result);
    }



    /**
     * 删除记录
     * author: panzhaochao
     * date: 2019/5/21 21:56
     *
     * @return mixed
     */
    public function delete($where = null,$params = []){
        $this->where($where,$params);
        if(empty($this->container['where'])){
            return 0;
        }
        try{
            $this->sql = 'DELETE FROM'.$this->container['table'].$this->container['where'];
            $this->sqlParams[] = ['sql'=>$this->sql,'params'=>$this->params];
            $result = $this->execute($this->sql,$this->params);
        }catch(\Throwable $e){
            Log::write(exceptionMessage($e));   //写入错误日志表
            report($e);   //写入日志文件
            $result = 0;
        }
        $this->resetParams();
        return $result;
    }


    /**
     * 添加数据
     * author: panzhaochao
     * date: 2019-10-26 18:04
     *
     * @param array $data
     *
     * @return int
     */
    public function insert($data = []){
        try{
            $this->data($data);
            $this->sql = 'INSERT INTO'.$this->container['table'].$this->container['insert_data'];
            $this->sqlParams[] = ['sql'=>$this->sql,'params'=>$this->params];
            $result = $this->execute($this->sql,$this->params);
        }catch(\Throwable $e){
            Log::write(exceptionMessage($e));   //写入错误日志表
            report($e);   //写入日志文件
            $result = 0;
        }
        $this->resetParams();
        return $result;
    }

    /**
     * 更新数据
     * author: panzhaochao
     * date: 2019-10-26 17:06
     *
     * @param array $data   //要更新的数据
     * @param null  $where   //更新的条件
     * @param null  $params  //更新的参数
     *
     * @return int|mixed
     */
    public function update($data = [],$where = null, $params = []){
        $this->data($data);
        $this->where($where,$params);
        if(empty($this->container['where'])){
            return 0;
        }
        try{
            $this->sql = 'UPDATE'.$this->container['table'].$this->container['update_data'].$this->container['where'];
            $this->sqlParams[] = ['sql'=>$this->sql,'params'=>$this->params];
            $result = $this->execute($this->sql,$this->params);
        }catch(\Throwable $e){
            Log::write(exceptionMessage($e));   //写入错误日志表
            report($e);   //写入日志文件
            $result = 0;
        }
        $this->resetParams();
        return $result;
    }



    /**
     * 执行原生SQL
     *
     * @param string $sql  SQL语句
     * @param array  $argv SQL参数
     *
     * @return mixed
     */
    public function execute(string $sql = null, array $params = [])
    {
        $sql = trim($sql);
        $sqlArray = explode(' ', $sql);
        switch (strtoupper(current($sqlArray))) {
            case 'SELECT':
                $result = $this->db->select($sql, $params);
                $result = !empty($result) ? $this->objectToArray($result) :[];
                break;
            case 'INSERT':
                $this->db->insert($sql, $params);
                $result = $this->db->getPdo()->lastInsertId();
                break;
            case 'UPDATE':
                $result = $this->db->update($sql, $params);
                break;
            case 'DELETE':
                $result = $this->db->delete($sql, $params);
                break;
            default:
                $result = false;
                break;
        }
        return $result;
    }





    /**
     * 查询sql语句
     * author: panzhaochao
     * date: 2019/5/22 9:59
     */
    private function getSql(){
        return $this->sqlParams;
    }


    /**
     * 开启事务
     * author: panzhaochao
     * date: 2019/5/22 10:02
     */
    private function startTrans(){
        $this->sqlParams[] = ['sql'=>'begin transaction'];
        $this->db->beginTransaction();
    }

    /**
     * 提交事务
     * author: panzhaochao
     * date: 2019/5/22 10:02
     */
    private function commit(){
        $this->sqlParams[] = ['sql'=>'commit'];
        $this->db->commit();
    }

    /**
     * 回滚事务
     * author: panzhaochao
     * date: 2019/5/22 10:03
     */
    private function rollBack(){
        $this->sqlParams[] = ['sql'=>'rollback'];
        $this->db->rollBack();
    }
    /**
     * 对象转化成数组
     * author: panzhaochao
     * date: 2019/5/21 21:13
     *
     * @param $object
     *
     * @return mixed
     */
    private function objectToArray($object){
        return json_decode(json_encode($object),true);
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
        return $this->$method(...$args);
    }
}
