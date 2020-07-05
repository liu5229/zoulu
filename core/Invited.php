<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Class Invited extends AbstractController {
    protected $length = 8;
    
    public function createCode() {
//        $createList = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
        $createList = '0123456789';
        $code = '';
        for($i=0;$i<$this->length;$i++) {
            if ($i == 0) {
                $code .= rand(1, 9);
            } else {
                $code .= $createList{rand(0, 9)};
            }
//            $code .= $createList{rand(0, 33)};
        }
        $sql = 'SELECT COUNT(user_id) FROM t_user WHERE invited_code = ?';
        $isExist = $this->db->getOne($sql, $code);
        if ($isExist) {
            return $this->createCode();
        }
        return $code;
    }
}