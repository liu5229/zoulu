<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class AbstractModel {
    protected $temp = array();
    
    public function __get ($name) {
        if (!isset($this->temp[$name])) {
            switch ($name) {
                case 'db':
                    $this->temp['db'] = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
                    $this->temp['db']->exec("SET time_zone = '+8:00'");
                    $this->temp['db']->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
                    break;
                default :
                    throw new \Exception("Can't find model plugin " . $name);
            }
        }
        return $this->temp[$name];
    }
}