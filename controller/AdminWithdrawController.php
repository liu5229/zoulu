<?php 

Class AdminWithdrawController extends AbstractController {
    public function listAction () {
        $whereArr = array('1 = 1');
        $dataArr = array();
        
        if (isset($_POST['status']) && $_POST['status']) {
            $whereArr[] = 'w.withdraw_status = :withdraw_status';
            $dataArr['withdraw_status'] = $_POST['status'];
        }
        
        if (isset($_POST['method']) && $_POST['method']) {
            $whereArr[] = 'w.withdraw_method = :withdraw_method';
            $dataArr['withdraw_method'] = $_POST['method'];
        }
        $where = 'WHERE ' . implode(' AND ', $whereArr);
        $sql = "SELECT COUNT(*) FROM t_withdraw w " . $where;
        $totalCount = $this->db->getOne($sql, $dataArr);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT w.*, u.create_time user_time, u.brand, u.model, u.phone_number, u.umeng_score
                    FROM t_withdraw w
                    LEFT JOIN t_user u USING(user_id)
                    $where
                    ORDER BY w.withdraw_id DESC 
                    LIMIT " . $this->page;
            $list = $this->db->getAll($sql, $dataArr);
        }
        foreach ($list as &$info) {
            $sql = 'SELECT COUNT(withdraw_id) count, IFNULL(SUM(withdraw_amount), 0) total FROM t_withdraw WHERE user_id = ? AND withdraw_status = "success"';
            $info = array_merge($info, $this->db->getRow($sql, $info['user_id']));
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
    
    public function actionAction () {
        if (isset($_POST['action']) && isset($_POST['withdraw_id'])) {
            switch ($_POST['action']) {
                case 'failed' :
                    $sql = 'UPDATE t_withdraw SET withdraw_status = "failure", withdraw_remark = ? WHERE withdraw_id = ?';
                    $return = $this->db->exec($sql, $_POST['withdraw_remark'] ?? '', $_POST['withdraw_id']);
                    break;
                case 'success':
//                    $alipay = new Alipay();
//                    $returnStatus = $alipay->transfer(array(
//                        'price' => $userInfo['withdraw_amount'],
//                        'phone' => $userInfo['alipay_account'],
//                        'name' => $userInfo['alipay_name']));
                    $sql = 'SELECT * FROM t_withdraw WHERE withdraw_id = ?';
                    $payInfo = $this->db->getRow($sql, $_POST['withdraw_id']);
                    switch ($payInfo['withdraw_method']) {
                        case 'alipay':
                            $returnStatus = TRUE;
                            break;
                        case 'wechat':
                            $wechatPay = new Wxpay();
                            $returnStatus = $wechatPay->transfer($payInfo['withdraw_amount'], $payInfo['wechat_openid']);
                            break;
                        default: 
                            throw new \Exception("Operation failure");
                    }
                    if (TRUE === $returnStatus) {
                        $sql = "INSERT INTO t_gold SET
                                user_id = :user_id,
                                change_gold = :change_gold,
                                gold_source = :gold_source,
                                change_type = :change_type,
                                relation_id = :relation_id,
                                change_date = :change_date";
                        $this->db->exec($sql, array(
                            'user_id' => $payInfo['user_id'],
                            'change_gold' => $payInfo['withdraw_gold'],
                            'gold_source' => 'withdraw',
                            'change_type' => 'out',
                            'relation_id' => $_POST['withdraw_id'],
                            'change_date' => date('Y-m-d')
                        ));
                        $sql = 'UPDATE t_withdraw SET withdraw_status = "success" WHERE withdraw_id = ?';
                        $return = $this->db->exec($sql, $_POST['withdraw_id']);
                    } else {
                        //to do failure reason from api return
                        $sql = 'UPDATE t_withdraw SET withdraw_status = "failure", withdraw_remark = ? WHERE withdraw_id = ?';
                        $return = $this->db->exec($sql, $returnStatus, $_POST['withdraw_id']);
                    }
                    break;
            }
            if ($return) {
                return array();
            } else {
                throw new \Exception("Operation failure");
            }
        }
        throw new \Exception("Error Request");
    }
}