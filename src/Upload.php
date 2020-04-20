<?php

namespace App\Extend;

/**
 * 文件上传类，支持单文件上传和多文件上传(需要开启fileinfo扩展)
 */
class Upload
{
    private $upload_path = '/uploads/';
    private $allowed_types = array('jpeg', 'jpg', 'png', 'gif');//允许上传的文件
    private $max_size = 1;//单位M
    private $field = '';  //上传表单file的name
    public $error_info = '';  //错误消息
    private $show_sub = 1;    //子目录 默认2016/5/2

    public function __construct($config = array())
    {
        if (!empty($config)) {
            foreach ($config as $key => $val) {
                $this->$key = $val;
            }
        }
        if (isset($config['upload_path'])) {
            $this->upload_path = '.' . rtrim($config['upload_path'], '/') . '/';
        }
        if (isset($config['field'])) {
            $this->field = isset($_FILES[$config['field']]) ? $_FILES[$config['field']] : '';
        }
    }

    //单文件和多文件上传
    public function upload()
    {
        $file_list = $this->field;
        if (isset($file_list['name']) && is_array($file_list['name'])) {
            foreach ($file_list['name'] as $key => $val) {
                $file_info['name'] = $file_list['name'][$key];
                $file_info['type'] = $file_list['type'][$key];
                $file_info['tmp_name'] = $file_list['tmp_name'][$key];
                $file_info['error'] = $file_list['error'][$key];
                $file_info['size'] = $file_list['size'][$key];
                $this->field = $file_info;
                $result[$key] = $this->upload_one();
            }
        } else {
            $result = $this->upload_one();
        }
        return $result;
    }

    private function upload_one()
    {
        if (!is_array($this->allowed_types)) {
            throw new \Exception('allowed_types参数错误，请传入一个数组');
        }
        if (empty($this->field)) {
            throw new \Exception('请选择要上传的文件');
        }
        //上传错误
        if ($err_info = $this->check_err()) {
            throw new \Exception($err_info);
        }
        //文件类型错误
        if (!$this->check_allowed_types()) {
            throw new \Exception('请上传' . implode(',', $this->allowed_types) . '类型的文件');
        }
        //上传过大文件
        if (!$this->check_max_size()) {
            throw new \Exception('上传文件过大，不能超过' . $this->max_size . 'M');
        }
        //是不是http上传的
        if (!$this->check_file()) {
            throw new \Exception('上传文件被破坏');
        }
        //创建上传目录
        if (!$this->make_upload_dir()) {
            throw new \Exception('上传目录创建失败，请检查目录权限');
        }
        if (!$result = $this->move_file()) {
            throw new \Exception('移动文件失败');
        } else {
            return $result;
        }
    }

    //上传错误码
    private function check_err()
    {
        $errno = $this->field['error'];
        if ($errno == 0) return false;
        switch ($errno) {
            case 1:
                $error_info = '文件大小超出了php.ini中的大小限制';
                break;
            case 2:
                $error_info = '文件大小超出了表单中设定的最大值';
                break;
            case 3:
                $error_info = '上传出错，文件部分被上传';
                break;
            case 4:
                $error_info = '没有文件被上传';
                break;
        }
        return $error_info;
    }

    //判断后缀
    private function check_allowed_types()
    {
        $this->file_ext = ltrim(strrchr($this->field['name'], '.'), '.');
        if (!in_array(strtolower($this->file_ext), $this->allowed_types)) {
            return false;
        }
        return true;
    }

    //检查文件大小
    private function check_max_size()
    {
        if ($this->field['size'] > $this->max_size * 1024 * 1024) {
            return false;
        } else {
            return true;
        }
    }

    // 检测该临时文件是否为上传的文件
    private function check_file()
    {
        if (!is_uploaded_file($this->field['tmp_name'])) {
            return false;
        } else {
            return true;
        }
    }

    //创建目录
    private function make_upload_dir()
    {
        if ($this->show_sub == 1) {
            $this->sub_dir = date('Ymd') . '/';
        } else {
            $this->sub_dir = '';
        }
        if (is_file($this->upload_path . $this->sub_dir)) {
            return false;
        } else if (!file_exists($this->upload_path . $this->sub_dir)) {
            return mkdir($this->upload_path . $this->sub_dir, 0777, true);
        } else {
            return true;
        }
    }

    //移动上传文件
    private function move_file()
    {
        $upload_filename = md5(uniqid('', true) . uniqid('', true) . uniqid('', true)) . '.' . $this->file_ext;
        $result = move_uploaded_file($this->field['tmp_name'], $this->upload_path . $this->sub_dir . $upload_filename);
        if ($result) {
            return ltrim($this->upload_path, '.') . $this->sub_dir . $upload_filename;
        } else {
            return false;
        }
    }


}

