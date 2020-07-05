<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//生成以前没有邀请码用户的邀请码

require_once __DIR__ . '/../../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$sql = 'SELECT user_id FROM t_user WHERE invited_code = ""';
$userList = $db->getColumn($sql);
$invitedClass = new Invited();
foreach ($userList as $userId) {
    $newCode = $invitedClass->createCode();
    $sql = 'UPDATE t_user SET invited_code = ? WHERE user_id = ?';
    $db->exec($sql, $newCode, $userId);
}

echo 'done';