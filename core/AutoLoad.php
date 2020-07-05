<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class AutoLoad {
    
    public static function load ($className) {
//        $fileAutoFindArr = array(ROOT_DIR . 'controller/', ROOT_DIR . 'core/');
        $fileAutoFindArr = array(
            'controller' => CONTROLLER_DIR,
            'core' => CORE_DIR,
            'model' => MODEL_DIR,
        );
        foreach ($fileAutoFindArr as $fileDir) {
            $file = $fileDir . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        throw new \Exception("Can't autoload class " . $className);
    }

    /**
     * register autoload
     */
    public static function register()
    {
        spl_autoload_register(array('static', 'load'));
    }
}