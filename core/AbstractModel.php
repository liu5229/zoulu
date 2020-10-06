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
                    $this->temp['db'] = Db::getDbInstance();
                    break;
                case 'model' :
                    $this->temp['model'] = new Model();
                    break;
                default :
                    throw new \Exception("Can't find model plugin " . $name);
            }
        }
        return $this->temp[$name];
    }
}