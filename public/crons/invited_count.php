<?php

//累计邀请多次好友的用户发放奖励
//  一小时执行一次
require_once __DIR__ . '/../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$model = new Model();

$sql = 'SELECT activity_status FROM t_activity WHERE activity_type = "invited_count"';
$status = $db->getOne($sql);
if (!$status) {
    echo 'activity end';exit;
}

$variableName = 'invited_id';
$sql = 'SELECT variable_value FROM t_variable WHERE variable_name = ?';
$invitedId = $db->getOne($sql, $variableName);

if (!$invitedId) {
    $invitedId = 0;
}

$sql = 'SELECT * FROM t_user_invited WHERE id > ?';
$invitedList = $db->getAll($sql, $invitedId);

foreach ($invitedList as $invitedInfo) {
    $sql = 'SELECT COUNT(*) FROM t_user_invited WHERE user_id = ? AND id <= ?';
    $invitedCount = $db->getOne($sql, $invitedInfo['user_id'], $invitedInfo['id']);
    
    $sql = 'SELECT config_id, award_min FROM t_award_config WHERE config_type = ? AND counter_min = ?';
    $invitedAwardInfo = $db->getRow($sql, 'invited_count', $invitedCount);
    if ($invitedAwardInfo) {
        $model->user2->updateGold(array(
            'user_id' => $invitedInfo['user_id'],
            'gold' => $invitedAwardInfo['award_min'],
            'source' => 'invited_count',
            'type' => 'in',
            'relation_id' => $invitedAwardInfo['config_id']));
    }
    $sql = 'REPLACE INTO t_variable SET variable_name = ?, variable_value = ?';
    $db->exec($sql, $variableName, $invitedInfo['id']);
} 

echo 'done';