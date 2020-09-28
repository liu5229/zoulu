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
                    $return = $this->model->withdraw->updateStatus(array('withdraw_status' => 'failure', 'withdraw_remark' => $_POST['withdraw_remark'] ?? '', 'withdraw_id' => $_POST['withdraw_id']));
                    break;
                case 'success':
//                    $alipay = new Alipay();
//                    $returnStatus = $alipay->transfer(array(
//                        'price' => $userInfo['withdraw_amount'],
//                        'phone' => $userInfo['alipay_account'],
//                        'name' => $userInfo['alipay_name']));
                    $sql = 'SELECT * FROM t_withdraw WHERE withdraw_id = ?';
                    $payInfo = $this->db->getRow($sql, $_POST['withdraw_id']);
                    if (in_array($payInfo['withdraw_amount'], array(1, 5))) {
                        $sql = 'SELECT COUNT(withdraw_id) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = ? AND withdraw_status = ?';
                        if ($this->db->getOne($sql, $payInfo['user_id'], $payInfo['withdraw_amount'], 'success')) {
                            //to do failure reason from api return
                            $return = $this->model->withdraw->updateStatus(array('withdraw_status' => 'failure', 'withdraw_remark' => '新用户专享', 'withdraw_id' => $_POST['withdraw_id']));
                            break;
                        }
                    }
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
                        $this->model->gold->updateGold(array('user_id' => $payInfo['user_id'], 'gold' => $payInfo['withdraw_gold'], 'source' => "withdraw", 'type' => "out", 'relation_id' => $_POST['withdraw_id']));
                        $return = $this->model->withdraw->updateStatus(array('withdraw_status' => 'success', 'withdraw_id' => $_POST['withdraw_id']));
                    } else {
                        $return = $this->model->withdraw->updateStatus(array('withdraw_status' => 'failure', 'withdraw_remark' => $returnStatus, 'withdraw_id' => $_POST['withdraw_id']));
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