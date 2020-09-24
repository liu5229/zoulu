<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class ApiReturn {
    
    protected $msg = '';
    protected $data = array();
    protected $code = 200;
    
    public function __construct ($data = '', $code = 200, $msg = '') {
        if ($data == '') {
            $data = (object)array();
        }
        $this->data = $data;
        $this->code = $code;
        $this->msg = $msg;
    }
    
    public static function init($data, $code = 200, $msg = '')
    {
        return new self($data, $code, $msg);
    }
    
    public function __set($name, $value) {
        $this->$name = $value;
    }
    
    public function __get($name) {
        return $this->$name;
    }
}