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
        $sql = 'SELECT COUNT(withdraw_id) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = 1 AND withdraw_status = "success"';
        if ($db->getOne($sql, $withdrawInfo['user_id'])) { //to do failure reason from api return
            $model->withdraw->updateStatus(array('withdraw_status' => 'failure', 'withdraw_remark' => '新用户专享', 'withdraw_id' => $withdrawInfo['withdraw_id']));
        } else {
            $returnStatus = $wechatPay->transfer($withdrawInfo['withdraw_amount'], $withdrawInfo['wechat_openid']);
            if (TRUE === $returnStatus) {
                $model->gold->updateGold(array('user_id' => $withdrawInfo['user_id'], 'gold' => $withdrawInfo['withdraw_gold'], 'source' => "withdraw", 'type' => "out", 'relation_id' => $withdrawInfo['withdraw_id']));
                $model->withdraw->updateStatus(array('withdraw_status' => 'success', 'withdraw_id' => $withdrawInfo['withdraw_id']));
            } else {
                //to do failure reason from api return
                $model->withdraw->updateStatus(array('withdraw_status' => 'failure', 'withdraw_remark' => $returnStatus, 'withdraw_id' => $withdrawInfo['withdraw_id']));
            }
        }
    }

    sleep(3);
}
echo 'done';