<?php

//删除昨天前未领取的用户接收到金币  这些数据也没有任何作用
//每天1：00执行一次

require_once __DIR__ . '/../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$model = new Model();

$variableName = 'remove_news_gold_id';
$sql = 'SELECT variable_value FROM t_variable WHERE variable_name = ?';
$userIdStart = $db->getOne($sql, $variableName) ?: 0;

$createTime = date('Y-m-d 23:59:59', strtotime('-30 day'));

$sql = 'SELECT u.user_id, g.change_gold, g.gold_id
        FROM t_user u
        LEFT JOIN t_withdraw w ON w.user_id = u.user_id
        LEFT JOIN t_gold g ON g.user_id = u.user_id AND g.gold_source = "newer"
        WHERE w.withdraw_id IS NULL
        AND u.user_id > ?
        AND u.create_time <= ?';
$userList = $db->getAll($sql, $userIdStart, $createTime);
foreach ($userList as $userInfo) {
    $params = array('user_id' => $userInfo['user_id'],
        'gold' => $userInfo['change_gold'] ?: 0,
        'source' => "newer_invalid",
        'type' => "out",
        'relation_id' => $userInfo['gold_id'] ?: 0
    );
    $model->user2->updateGold($params);
    
    $sql = 'REPLACE INTO t_variable SET variable_name = ?, variable_value = ?';
    $db->exec($sql, $variableName, $userInfo['user_id']);
}

echo 'done';