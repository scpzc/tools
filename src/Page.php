<?php

namespace Scpzc\Tools;
/**
 * 分页类，支持按段分页
 */
class Page
{
    private $count; //总记录数
    private $offset = 2;  //数字分布前后的偏移量
    private $limit;
    private $uri;
    public $total_page; //总页数
    private $page_size = 10;  //每页显示记录数
    private $header = '条记录';
    private $prev = '上一页';
    private $next = '下一页';
    private $page_word = 'page';  //分页的参数
    private $segment = 0;         //采用分段分页的时候，第几段是分页
    private $line = false;            //采用-分布的时候
    public $start;             //记录开始位置
    private $isAjax = 0;         //分页是否用ajax
    public $page = 1;     //当前页数

    public function __construct($config = array())
    {
        if (!empty($config)) {
            foreach ($config as $key => $val) {
                $this->$key = $val;
            }
        }
        $totalPage = ceil($this->count / $this->page_size);
        $this->total_page = $totalPage ? $totalPage : 1;
        $this->uri = $this->getUri();
        $this->page = $this->setPage();
        $this->limit = $this->setLimit();
    }

    private function setPage()
    {
        $url = $_SERVER["REQUEST_URI"];
        $parse = parse_url($url);
        $path_arr = explode('/', $parse['path']);
        if (!empty($this->segment)) {
            $page = intval($path_arr[$this->segment]) ? intval($path_arr[$this->segment]) : 1;
        } elseif ($this->line == true) {
            preg_match('#\-(\d+)\.html#', $url, $temp);
            $page = $temp[1] ? $temp[1] : 1;
        } else {
            $page = !empty($_GET[$this->page_word]) ? intval($_GET[$this->page_word]) : 1;
        }
        return min($this->total_page, max(1, $page));
    }

    private function setLimit()
    {
        $this->start = ($this->page - 1) * $this->page_size;
        return "limit " . $this->start . ", {$this->page_size}";
    }

    private function getUri()
    {
        $url = $_SERVER["REQUEST_URI"];
        $parse = parse_url($url);
        //用斜线分隔
        if (!empty($this->segment)) {
            $path_arr = explode('/', $parse['path']);
            $path_arr[$this->segment] = '__PAGE__';
            if (!empty($parse['query'])) $query = '?' . $parse['query'];
            $url = implode('/', $path_arr) . $query;
            //用-分隔，用最后一个-后面的数字作为分页
        } elseif ($this->line == true) {
            $url = preg_replace('#\-\d+\.#', "-__PAGE__.", $url);
            if (!strpos($url, '__PAGE__')) {
                $url = $url . '0-__PAGE__.html';
            }
            //用?的形式
        } else {
            if (isset($parse["query"])) {
                parse_str($parse['query'], $params);
                unset($params[$this->page_word]);
                unset($params['t']);
                if (!empty($params)) {
                    $url = $parse['path'] . '?' . http_build_query($params) . '&';
                } else {
                    $url = $parse['path'] . '?';
                }
            } else {
                $url .= strpos($url, '?') ? '&' : "?";
            }
            $url = $url . $this->page_word . '=__PAGE__';
        }
        // $url .='&t='.time();
        return $url;
    }


    //首页
    private function first()
    {
        $html = "";
        if ($this->page == 1) {
            $html .= '';
        } else {
            $html .= " <a class='pages' href='" . str_replace('__PAGE__', 1, $this->uri) . "'>1</a><span class='pages'>...</span> ";
        }

        return $html;
    }

    //最后一页
    private function last()
    {
        $html = "";
        if ($this->page == $this->total_page)
            $html .= '';
        else
            $html .= " <span class='pages'>...</span> <a class='pages' href='" . str_replace('__PAGE__', $this->total_page, $this->uri) . "'>{$this->total_page}</a>";
        return $html;
    }

    //上一页
    private function prev()
    {
        $html = "";
        if ($this->page == 1) {
            $html .= '';
        } else {
            $html .= " <a class='pages' href='" . str_replace('__PAGE__', ($this->page - 1), $this->uri) . "'>{$this->prev}</a>";
        }
        return $html;
    }

    //下一页
    private function next()
    {
        $html = "";
        if ($this->page == $this->total_page) {
            $html .= '';
        } else {
            $html .= " <a class='next_page' href='" . str_replace('__PAGE__', ($this->page + 1), $this->uri) . "'>{$this->next}</a>";
        }
        return $html;
    }

    //数字分页
    private function pageList()
    {
        $link_page = "";
        if ($this->page > $this->offset + 1) {
            $link_page .= $this->first();
        }
        for ($i = $this->offset; $i >= 1; $i--) {
            $page = $this->page - $i;
            if ($page < 1) {
                continue;
            }
            $link_page .= " <a class='pages' href='" . str_replace('__PAGE__', $page, $this->uri) . "'>{$page}</a>";
        }
        $link_page .= " <span class='current'>{$this->page}</span> ";
        for ($i = 1; $i <= $this->offset; $i++) {
            $page = $this->page + $i;
            if ($page <= $this->total_page) {
                $link_page .= " <a class='pages' href='" . str_replace('__PAGE__', $page, $this->uri) . "'>{$page}</a>";
            } else {
                break;
            }
        }
        if ($this->page < $this->total_page - $this->offset) {
            $link_page .= $this->last();
        }
        return $link_page;
    }


    //跳转分页
    private function jumpPage()
    {
        if ($this->isAjax != 1) {
            return ' <span><input type="text" class="jump_page" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>' . $this->total_page . ')?' . $this->total_page . ':this.value;var uri=\'' . $this->uri . '\';location=uri.replace(\'__PAGE__\',page); return false;}" value="' . $this->page . '" style="width:50px"><input type="button" class="go_button" value="GO" onclick="javascript:var page=(this.previousSibling.value>' . $this->total_page . ')?' . $this->total_page . ':this.previousSibling.value;var uri=\'' . $this->uri . '\';location=uri.replace(\'__PAGE__\',page);"></span> ';
        } else {
            return ' <span><input type="text" class="jump_page" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>' . $this->total_page . ')?' . $this->total_page . ':this.value;var uri=\'' . $this->uri . '\';page_url=uri.replace(\'__PAGE__\',page);  ajax_page(page_url); return false;}" value="' . $this->page . '" style="width:50px"><input type="button" class="go_button" value="GO" onclick="javascript:var page=(this.previousSibling.value>' . $this->total_page . ')?' . $this->total_page . ':this.previousSibling.value;var uri=\'' . $this->uri . '\';page_url=uri.replace(\'__PAGE__\',page); ajax_page(page_url);"></span> ';
        }
    }

    //分页文本
    function pageStr($display = array(0, 1, 2, 3, 4, 5))
    {
        $html[0] = "<span class='total_page'>共有{$this->count}{$this->header}</span>";
        $html[1] = "<span class='total_page'>{$this->page}/{$this->total_page}页</span>";
        $html[2] = $this->prev();
        $html[3] = $this->pageList();
        $html[4] = $this->next();
        $html[5] = $this->jumpPage();
        $pageStr = '';
        foreach ($display as $index) {
            $pageStr .= $html[$index];
        }
        if ($this->isAjax == 1) {
            $pageStr = preg_replace("#<a[^>]+?href=['\"](.*?)['\"]>#is", '<a href="javascript:;" onclick="ajax_page(\'\1\')">', $pageStr);
        }
        return $pageStr;
    }

}

