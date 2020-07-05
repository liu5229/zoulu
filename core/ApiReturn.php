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
        if (DEBUG_MODE) {
            //add api log
            $logFile = LOG_DIR . 'access/' . date('Ymd') . '/';
            if (!is_dir($logFile)) {
                mkdir($logFile, 0755, true);
            }
            file_put_contents($logFile . 'access_' . date('H') . '.log', date('Y-m-d H:i:s') . '|' . ($_SERVER['REQUEST_URI'] ?? '') . '|' . json_encode(json_decode(file_get_contents("php://input"), TRUE)) . '|' . json_encode(array('code' => $code, 'data' => $data, 'msg' => $msg), JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
        }
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