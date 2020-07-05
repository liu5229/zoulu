<?php 

Class WalkController extends AbstractController {
    //提现汇率
    protected $withdrawalRate = 10000;
    protected $userId;
    
    public function init() {
        parent::init();
        $userId = $this->model->user->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        $this->userId = $userId;
    }
    
    /**
     * 401 无效更新
     * @return \ApiReturn
     * 
     */
    public function updateWalkAction () {
        if (!isset($this->inputData['stepCount'])) {
            return new ApiReturn('', 401, '无效更新');
        }
        $walkReward = new WalkCounter($this->userId, $this->inputData['stepCount']);
        return new ApiReturn(array('stepCount' => $walkReward->getStepCount()));
    }

    /**
     * 501 无效获取
     * @return \ApiReturn
     * 
     */
    public function taskAction() {
        if (!isset($this->inputData['type'])) {
            return new ApiReturn('', 501, '无效获取');
        }
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $activityInfo = $this->db->getRow($sql, $this->inputData['type']);
        if (!$activityInfo) {
            return new ApiReturn('', 501, '无效获取');
        }
        $today = date('Y-m-d');
        switch ($this->inputData['type']) {
            case 'walk':
            case 'walk_stage':
                $walkReward = new WalkCounter($this->userId);
                return new ApiReturn($walkReward->getReturnInfo($this->inputData['type']));
                break;
            case 'newer':
                return new ApiReturn('', 501, '无效获取');
                break;
            case 'wechat':
                $unionId = $this->model->user->userInfo($this->userId, 'unionid');
                $sql = 'SELECT activity_award_min FROM t_activity WHERE activity_type = "wechat"';
                return new ApiReturn(array('isBuild' => $unionId ? 1 : 0, 'award' => $this->db->getOne($sql)));
                break;
//            case 'phone':
//                $phoneNumber = $this->model->user->userInfo($this->userId, 'phone_number');
//                $sql = 'SELECT activity_award_min FROM t_activity WHERE activity_type = "phone"';
//                return new ApiReturn(array('isBuild' => $phoneNumber ? 1 : 0, 'award' => $this->db->getOne($sql)));
//                break;
            case 'sign':
                $sql = 'SELECT check_in_days FROM t_user WHERE user_id = ?';
                $checkInDays = $this->db->getOne($sql, $this->userId);
                $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ?';
                $todayInfo = $this->db->getRow($sql, $this->userId, $today, $this->inputData['type']);
                if(!$todayInfo) {
                    $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ? AND receive_status = 1 ORDER BY receive_id DESC LIMIT 1';
                    $isSignLastDay = $this->db->getOne($sql, $this->userId, date('Y-m-d', strtotime("-1 day")), $this->inputData['type']);
                    if (!$isSignLastDay) {
                        $checkInDays = 0;
                        $sql = 'UPDATE t_user SET check_in_days = ? WHERE user_id = ?';
                        $this->db->exec($sql, 0, $this->userId);
                    }
                    //获取奖励金币范围
                    $sql = 'SELECT award_min FROM t_award_config WHERE config_type = :type AND counter_min = :counter';
                    $awardRow = $this->db->getRow($sql, array('type' => 'sign', 'counter' => (($checkInDays + 1) % 7) ?? 7));
                    
                    $sql = 'INSERT INTO t_gold2receive SET user_id = ?, receive_date = ?, receive_type = ?, receive_gold = ?';
                    $this->db->exec($sql, $this->userId, $today, $this->inputData['type'], $awardRow['award_min']);
                }
                $fromDate = $today;
                $checkInReturn = array('checkInDays' => $checkInDays, 'checkInInfo' => array());
                if ($checkInDays) {
                    $checkInDays -= ($todayInfo['receive_status'] ?? 0);
                    $fromDate = date('Y-m-d', strtotime('-' . $checkInDays . 'days'));
                }
                $sql = 'SELECT receive_id id , receive_gold num, receive_status isReceive, is_double isDouble, IF(receive_date="' . $today . '", 1, 0) isToday FROM t_gold2receive WHERE user_id = ? AND receive_date >= ? AND receive_type = ? ORDER BY receive_id';
                $checkInInfo = $this->db->getAll($sql, $this->userId, $fromDate, $this->inputData['type']);
                
                $i = 0;
                $sql = 'SELECT counter_min, award_min FROM t_award_config WHERE config_type = "sign" ORDER BY config_id ASC';
                $checkInConfigList = $this->db->getAll($sql);
                foreach ($checkInConfigList as $config) {
                    $checkInReturn['checkInInfo'][] = array_merge(array('day' => $config['counter_min'], 'award' => $config['award_min']), $checkInInfo[$i] ?? array());
                    $i++;
                }
                return new ApiReturn($checkInReturn);
                break;
            default :
                $sql = 'SELECT COUNT(*) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ?';
                $todayCount = $this->db->getOne($sql, $this->userId, $today, $this->inputData['type']);
                if (!$todayCount) {
                    //第一次领取
                    $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ? ORDER BY receive_id DESC LIMIT 1';
                    $historyLastdayInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d', strtotime("-1 day")), $this->inputData['type']);
                    if ($historyLastdayInfo && strtotime($historyLastdayInfo['end_time']) > time()) {
                        $endTime = $historyLastdayInfo['end_time'];
                    } else {
                        $endTime = date('Y-m-d H:i:s');
                    }
                    $gold = rand($activityInfo['activity_award_min'], $activityInfo['activity_award_max']);
                    $sql = 'INSERT INTO t_gold2receive SET user_id = ?, receive_date = ?, receive_type = ?, end_time = ?, receive_gold = ?';
                    $this->db->exec($sql, $this->userId, $today, $this->inputData['type'], date('Y-m-d H:i:s'), $gold);
                }
                $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ? ORDER BY receive_id DESC LIMIT 1';
                $historyInfo = $this->db->getRow($sql, $this->userId, $today, $this->inputData['type']);
                $return = array();
                $sql = 'SELECT COUNT(*) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ? AND receive_status = 1';
                $receiveCount = $this->db->getOne($sql, $this->userId, $today, $this->inputData['type']);
                $return = array('receiveCount' => $receiveCount, 
                    'endTime' => strtotime($historyInfo['end_time']) * 1000,
                    'isReceive' => $historyInfo['receive_status'],
                    'id' => $historyInfo['receive_id'],
                    'num' => $historyInfo['receive_gold'],
                    'serverTime' => time() * 1000,
                    'countMax' => $activityInfo['activity_max']);
                if ('tab' == $this->inputData['type']) {
                    //to do移动到数据库中
                    $return['probability'] = $activityInfo['activity_remark'];
                }
                return new ApiReturn($return);
        }
    }
    
    /**
     * 402 无效领取
     * 403 重复领取
     * 404 今日已签到
     * 405 领取时间未到
     * 406 先获取任务信息
     * @return \ApiReturn
     * 
     */
    public function getAwardAction () {
        if (!isset($this->inputData['type'])) {
            return new ApiReturn('', 402, '无效领取');
        }
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $activityInfo = $this->db->getRow($sql, $this->inputData['type']);
        if (!$activityInfo) {
            return new ApiReturn('', 402, '无效领取');
        }
        $today = date('Y-m-d');
        switch ($this->inputData['type']) {
            case 'walk':
            case 'walk_stage':
                $walkReward = new WalkCounter($this->userId, $this->inputData['stepCount'] ?? 0);
                $receiveInfo = $walkReward->verifyReceive(array(
                   'receive_id' => $this->inputData['id'] ?? 0,
                   'receive_gold' => $this->inputData['num'] ?? 0,
                   'receive_type' => $this->inputData['type'] ?? '',
                ));
                if ($receiveInfo) {
                    if (1 == $receiveInfo['receive_status']) {
                        return new ApiReturn('', 403, '重复领取');
                    } else {
                        $doubleStatus = $this->inputData['isDouble'] ?? 0;
                        $updateStatus = $this->model->user->updateGold(array(
                            'user_id' => $this->userId,
                            'gold' => $this->inputData['num'] * ($doubleStatus + 1),
                            'source' => $this->inputData['type'],
                            'type' => 'in',
                            'relation_id' => $this->inputData['id']));
                        if (TRUE === $updateStatus) {
                            $walkReward->receiveSuccess($this->inputData['id'], $doubleStatus);
                            $goldInfo = $this->model->user->getGold($this->userId);
                            return new ApiReturn(array('awardGold' => $this->inputData['num'] * ($doubleStatus + 1), 'currentGold' => $goldInfo['currentGold']));
                        }
                        return $updateStatus;
                    }
                } else {
                    return new ApiReturn('', 402, '无效领取');
                }
                break;
            case 'newer':
                return new ApiReturn('', 402, '无效领取');
                break;
//            case 'phone':
            case 'wechat':
                return new ApiReturn('', 402, '无效领取');
                break;
            case 'sign':
                $sql = 'SELECT receive_id, receive_status, receive_gold, end_time, is_double
                        FROM t_gold2receive
                        WHERE receive_id =:receive_id
                        AND user_id = :user_id
                        AND receive_gold = :receive_gold
                        AND receive_type = :receive_type
                        AND receive_date = :receive_date';
                $historyInfo = $this->db->getRow($sql, array(
                   'receive_id' => $this->inputData['id'] ?? 0,
                   'user_id' => $this->userId,
                   'receive_gold' => $this->inputData['num'] ?? 0,
                   'receive_type' => $this->inputData['type'] ?? '',
                   'receive_date' => $today,
                ));
                if (!$historyInfo) {
                    return new ApiReturn('', 402, '无效领取');
                }
                $doubleStatus = $this->inputData['isDouble'] ?? 0;
                $secondDoubleStatus = $this->inputData['secondDou'] ?? 0;
                if ($historyInfo['receive_status']) {
                    if (!$secondDoubleStatus) {  
                        return new ApiReturn('', 404, '今日已签到');
                    } elseif ($historyInfo['is_double']) {
                        return new ApiReturn('', 402, '无效领取');
                    }
                } else {
                    $sql = 'UPDATE t_user SET check_in_days = check_in_days + 1 WHERE user_id = ?';
                    $this->db->exec($sql, $this->userId);
                }
                $updateStatus = $this->model->user->updateGold(array(
                        'user_id' => $this->userId,
                        'gold' => $historyInfo['receive_gold'] * ($doubleStatus + 1),
                        'source' => $this->inputData['type'],
                        'type' => 'in',
                        'relation_id' => $historyInfo['receive_id']));
                //奖励金币成功
                if (TRUE === $updateStatus) {
                    $sql = 'UPDATE t_gold2receive SET receive_status = 1, is_double = ? WHERE receive_id = ?';
                    $this->db->exec($sql, ($secondDoubleStatus || $doubleStatus) ? 1 : 0, $historyInfo['receive_id']);
//                    $walkReward->receiveSuccess($this->inputData['id']);
                    $goldInfo = $this->model->user->getGold($this->userId);
                    return new ApiReturn(array('awardGold' => $historyInfo['receive_gold'], 'currentGold' => $goldInfo['currentGold']));
                }
                return $updateStatus;
                break;
            default :
                $sql = 'SELECT receive_id, receive_status, receive_gold, end_time
                        FROM t_gold2receive
                        WHERE receive_id =:receive_id
                        AND user_id = :user_id
                        AND receive_gold = :receive_gold
                        AND receive_type = :receive_type
                        AND receive_date = :receive_date';
                $historyInfo = $this->db->getRow($sql, array(
                   'receive_id' => $this->inputData['id'] ?? 0,
                   'user_id' => $this->userId,
                   'receive_gold' => $this->inputData['num'] ?? 0,
                   'receive_type' => $this->inputData['type'] ?? '',
                   'receive_date' => $today,
                ));
                if (!$historyInfo) {
                    return new ApiReturn('', 402, '无效领取');
                }
                if ($historyInfo['receive_status']) {
                    return new ApiReturn('', 403, '重复领取');
                }
                if (strtotime($historyInfo['end_time']) > time()) {
                    return new ApiReturn('', 405, '领取时间未到');
                }
                $doubleStatus = $this->inputData['isDouble'] ?? 0;
                $updateStatus = $this->model->user->updateGold(array(
                        'user_id' => $this->userId,
                        'gold' => $historyInfo['receive_gold'] * ($doubleStatus + 1),
                        'source' => $this->inputData['type'],
                        'type' => 'in',
                        'relation_id' => $historyInfo['receive_id']));
                //奖励金币成功
                if (TRUE === $updateStatus) {
                    $sql = 'UPDATE t_gold2receive SET receive_status = 1, is_double = ? WHERE receive_id = ?';
                    $this->db->exec($sql, $doubleStatus, $historyInfo['receive_id']);
                    
                    $sql = 'SELECT COUNT(*) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ?';
                    $activityCount = $this->db->getOne($sql, $this->userId, $today, $this->inputData['type']);
                    
                    if (!$activityInfo['activity_max'] || $activityCount < $activityInfo['activity_max']) {
                        $endDate = date('Y-m-d H:i:s', strtotime('+' . $activityInfo['activity_duration'] . 'minute'));
                        $gold = rand($activityInfo['activity_award_min'], $activityInfo['activity_award_max']);
                        $sql = 'INSERT INTO t_gold2receive SET user_id = ?, receive_date = ?, receive_type = ?, end_time = ?, receive_gold = ?';
                        $this->db->exec($sql, $this->userId, $today, $this->inputData['type'], $endDate, $gold);
                    }
                    $goldInfo = $this->model->user->getGold($this->userId);
                    return new ApiReturn(array('awardGold' => $historyInfo['receive_gold'] * ($doubleStatus + 1), 'currentGold' => $goldInfo['currentGold']));
                }
                return $updateStatus;
                break;
        }
    }
    
    public function requestWithdrawalAction () {
        if (isset($this->inputData['amount']) && $this->inputData['amount']) {
            $withdrawalAmount = $this->inputData['amount'];
            $withdrawalGold = $this->inputData['amount'] * $this->withdrawalRate;
            //获取当前用户可用金币
            $sql = 'SELECT SUM(change_gold) FROM t_gold WHERE user_id = ?';
            $totalGold = $this->db->getOne($sql, $this->userId);
            $sql = 'SELECT SUM(withdraw_gold) FROM t_withdraw WHERE user_id = ? AND withdraw_status = "pending"';
            $bolckedGold = $this->db->getOne($sql, $this->userId);
            $currentGold = $totalGold - $bolckedGold;
            
            if ($withdrawalGold > $currentGold) {
                return new ApiReturn('', 502, '提现所需金币不足');
            }
            //是否绑定支付宝
            $sql = 'SELECT alipay_account, alipay_name FROM t_user WHERE user_id = ?';
            $alipayInfo = $this->db->getRow($sql, $this->userId);
            if (isset($alipayInfo['alipay_account']) && $alipayInfo['alipay_account'] && isset($alipayInfo['alipay_name']) && $alipayInfo['alipay_name']) {
                //1元提现只能一次 to do
                if (1 == $withdrawalAmount) {
                    $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = 1 AND (withdraw_status = "pending" OR withdraw_status = "success")';
                    if ($this->db->getOne($sql, $this->userId)) {
                        return new ApiReturn('', 503, '1元提现只支持一次');
                    }
                }
                $sql = 'INSERT INTO t_withdraw SET user_id = :user_id, 
                        withdraw_amount = :withdraw_amount, 
                        withdraw_gold = :withdraw_gold, 
                        withdraw_status = "pending", 
                        alipay_account = :alipay_account, 
                        alipay_name = :alipay_name';
                $this->db->exec($sql, array('user_id' => $this->userId,
                    'withdraw_amount' => $withdrawalAmount,
                    'withdraw_gold' => $withdrawalGold, 
                    'alipay_account' => $alipayInfo['alipay_account'],
                    'alipay_name' => $alipayInfo['alipay_name']));
                return new ApiReturn('');
            } else {
                return new ApiReturn('', 504, '未绑定支付宝账号');
            }
        } else {
            return new ApiReturn('', 501, '缺少提现金额');
        }
    }
    
    public function goldDetailAction () {
        $sql = 'SELECT gold_source source,change_gold value, change_type type, create_time gTime FROM t_gold WHERE user_id = ? AND create_time >= ? ORDER BY gold_id DESC';
        $goldDetail = $this->db->getAll($sql, $this->userId, date('Y-m-d 00:00:00', strtotime('-3 days')));
        $sql = 'SELECT activity_type, activity_name FROM t_activity ORDER BY activity_id DESC';
        $activeTypeList = $this->db->getPairs($sql);
        array_walk($goldDetail, function (&$v) use($activeTypeList) {
            switch ($v['type']) {
                case 'in':
                    $v['gSource'] = $activeTypeList[$v['source']] ?? $v['source'];
                    break;
                case 'out':
                    $v['gSource'] = 'withdraw' == $v['source'] ? '提现' : $v['source'];
                    $v['value'] = 0 - $v['value'];
                    break;
            }
            if ('system' == $v['source']) {
                $v['gSource'] = '官方操作';
            }
            $v['gTime'] = strtotime($v['gTime']) * 1000;
        });
        return new ApiReturn($goldDetail);    
    }
    
    public function withdrawDetailAction () {
        $statusArray = array('pending' => '审核中', 'success' => '审核成功', 'failure' => '审核失败');
        $sql = "SELECT withdraw_amount amount, withdraw_status status, create_time wTime  FROM t_withdraw WHERE user_id = ? ORDER BY withdraw_id DESC";
        $withdrawDetail = $this->db->getAll($sql, $this->userId);
        array_walk($withdrawDetail, function (&$v) use ($statusArray) {
            $v['status'] = $statusArray[$v['status']];
            $v['wTime'] = strtotime($v['wTime']) * 1000;
        });
        return new ApiReturn($withdrawDetail);    
    }
}