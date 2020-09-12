<?php

namespace Scpzc\Tools;
/**
 * api接口分页
 */
class Page
{
    private $totalRecord;//总记录
    public  $totalPage; //总页数
    public  $pageSize = 10;  //每页显示记录数
    public  $start;             //记录开始位置
    public  $page  = 1;     //当前页数

    public function __construct($config = [])
    {
        $this->totalRecord = $config['total_record'] ?? 0;
        $this->page        = $config['page'] ?? 1;
        $this->pageSize    = $config['page_size'] ?? 10;
        $this->pageSize    = max(1, $this->pageSize);
        $this->totalPage   = ceil($this->totalRecord / $this->pageSize);
        $this->start       = ($this->page - 1) * $this->pageSize;
    }

    /**
     * 分页信息
     * author: panzhaochao
     * date: 2020-03-18 22:53
     *
     * @return array|null
     */
    public function pageInfo(){
        if ($this->totalPage > 0) {
            $data = [
                'page'       => intval($this->page),
                'page_size'  => $this->pageSize,
                'total_page' => $this->totalPage,
                'total_record'=>$this->totalRecord,
            ];
        }
        return $data??null;
    }

}

