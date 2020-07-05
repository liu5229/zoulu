<?php


//记录以前没有分值的用户的分值

require_once __DIR__ . '/../../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$sql = 'SELECT user_id, umeng_token FROM t_user WHERE umeng_token != "" AND umeng_score = 0';
$userList = $db->getAll($sql);
$umengClass = new Umeng();
foreach ($userList as $userInfo) {
    $umengReturn = $umengClass->verify($userInfo['umeng_token']);
    if (TRUE !== $umengReturn && TRUE === $umengReturn->suc) {
        $sql = 'UPDATE t_user SET umeng_score = ? WHERE user_id = ?';
        $db->exec($sql, $umengReturn->score, $userInfo['user_id']);
    }
}

echo 'done';