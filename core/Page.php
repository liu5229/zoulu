<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Class Page {
    protected $limitStart = 0;
    protected $limitCount = 10;


    public function __construct() {
        if (isset($_POST['pageSize'])) {
            $this->limitCount = $_POST['pageSize'];
            if (isset($_POST['pageNo'])) {
                $this->limitStart = ($_POST['pageNo'] - 1) * $_POST['pageSize'];
            }
        }
    }
    
    public function __toString() {
        return $this->limitStart . ', ' . $this->limitCount;
    }
}