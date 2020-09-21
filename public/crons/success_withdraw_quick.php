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
            $model->gold->updateGold(array('user_id' => $withdrawInfo['user_id'], 'gold' => $withdrawInfo['withdraw_gold'], 'source' => "withdraw", 'type' => "out", 'relation_id' => $withdrawInfo['withdraw_id']));
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