<?php

//累计邀请多次好友的用户发放奖励
//  一小时执行一次
require_once __DIR__ . '/../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$model = new Model();
$wechatPay = new Wxpay();
while (true) {
    $sql = 'SELECT * FROM t_withdraw WHERE withdraw_status = "pending" AND withdraw_amount = 1 AND withdraw_method = "wechat" ORDER BY withdraw_id';
    $withdrawList = $db->getAll($sql);

    foreach ($withdrawList as $withdrawInfo) {
        $returnStatus = $wechatPay->transfer($withdrawInfo['withdraw_amount'], $withdrawInfo['wechat_openid']);
        if (TRUE === $returnStatus) {
            $sql = "INSERT INTO t_gold SET user_id = :user_id, change_gold = :change_gold, gold_source = :gold_source, change_type = :change_type, relation_id = :relation_id, change_date = :change_date";
            $db->exec($sql, array('user_id' => $withdrawInfo['user_id'], 'change_gold' => $withdrawInfo['withdraw_gold'], 'gold_source' => 'withdraw', 'change_type' => 'out', 'relation_id' => $withdrawInfo['withdraw_id'], 'change_date' => date('Y-m-d')));
            $sql = 'UPDATE t_withdraw SET withdraw_status = "success" WHERE withdraw_id = ?';
            $return = $db->exec($sql, $withdrawInfo['withdraw_id']);
        } else {
            //to do failure reason from api return
            $sql = 'UPDATE t_withdraw SET withdraw_status = "failure", withdraw_remark = ? WHERE withdraw_id = ?';
            $return = $db->exec($sql, $returnStatus, $withdrawInfo['withdraw_id']);
        }
    }

    sleep(3);
}
echo 'done';