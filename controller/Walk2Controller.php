<?php 

Class Walk2Controller extends WalkController {
    //提现汇率
    protected $withdrawalRate = 10000;
    protected $userId;
    
    /**
     * 验证用户有效性
     * 
     */
    public function init() {
        parent::init();
        $userId = $this->model->user2->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        $this->userId = $userId;
    }
    
    /**
     * 更新用户步数
     * 401 无效更新
     * @return \ApiReturn
     * 
     */
    public function updateWalkAction () {
        if (!isset($this->inputData['stepCount'])) {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
        $walkReward = new WalkCounter2($this->userId, $this->inputData['stepCount']);
        return new ApiReturn(array('stepCount' => $walkReward->getStepCount()));
    }

    /**
     * 获取任务信息
     * 501 无效获取
     * @return \ApiReturn
     * 
     */
    public function taskAction() {
        if (!isset($this->inputData['type'])) {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
        $taskClass = new Task();
        $return = $taskClass->getTask($this->inputData['type'], $this->userId);
        if ($return instanceof ApiReturn) {
            return $return;
        }
        return new ApiReturn($return);
    }
    
    /**
     * 领取任务奖励
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
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $activityInfo = $this->db->getRow($sql, $this->inputData['type']);
        if (!$activityInfo) {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
        if (!$activityInfo['activity_status']) {
            return new ApiReturn('', 204, '领取失败，请稍后再试');
        }
        $today = date('Y-m-d');
        switch ($this->inputData['type']) {
            case 'walk':
            case 'walk_stage':
                $walkReward = new WalkCounter2($this->userId, $this->inputData['stepCount'] ?? 0);
                $receiveInfo = $walkReward->verifyReceive(array(
                   'receive_id' => $this->inputData['id'] ?? 0,
                   'receive_gold' => $this->inputData['num'] ?? 0,
                   'receive_type' => $this->inputData['type'] ?? '',
                ));
                if ($receiveInfo) {
                    if (1 == $receiveInfo['receive_status']) {
                        return new ApiReturn('', 401, '您已领取过该奖励');
                    } else {
                        $doubleStatus = $this->inputData['isDouble'] ?? 0;
                        $updateStatus = $this->model->user2->updateGold(array(
                            'user_id' => $this->userId,
                            'gold' => $this->inputData['num'] * ($doubleStatus + 1),
                            'source' => $this->inputData['type'],
                            'type' => 'in',
                            'relation_id' => $this->inputData['id']));
                        if (TRUE === $updateStatus) {
                            $walkReward->receiveSuccess($this->inputData['id'], $doubleStatus);
                            $goldInfo = $this->model->user2->getGold($this->userId);
                            return new ApiReturn(array('awardGold' => $this->inputData['num'] * ($doubleStatus + 1), 'currentGold' => $goldInfo['currentGold']));
                        }
                        return $updateStatus;
                    }
                } else {
                    return new ApiReturn('', 205, '访问失败，请稍后再试');
                }
            case 'newer'://user2model/get-userInfo
            case 'wechat'://user2/build-wechat
            case 'do_invite'://user2/build-invited
            case 'invited'://user2/build-invited
            case 'invited_count'://脚本crons/invited_count.php
            case 'lottery'://activity2/lottery-award
                return new ApiReturn('', 205, '访问失败，请稍后再试');
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
                    return new ApiReturn('', 205, '访问失败，请稍后再试');
                }
                $doubleStatus = $this->inputData['isDouble'] ?? 0;
                $secondDoubleStatus = $this->inputData['secondDou'] ?? 0;
                if ($historyInfo['receive_status']) {
                    if (!$secondDoubleStatus) {  
                        return new ApiReturn('', 402, '今日已签到');
                    } elseif ($historyInfo['is_double']) {
                        return new ApiReturn('', 402, '今日已签到');
                    }
                } else {
                    $sql = 'UPDATE t_user SET check_in_days = check_in_days + 1 WHERE user_id = ?';
                    $this->db->exec($sql, $this->userId);
                }
                $updateStatus = $this->model->user2->updateGold(array(
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
                    $goldInfo = $this->model->user2->getGold($this->userId);
                    return new ApiReturn(array('awardGold' => $historyInfo['receive_gold'], 'currentGold' => $goldInfo['currentGold']));
                }
                return $updateStatus;
            default :
                //为了领取累计奖励移除receive_data=$today验证 
                $sql = 'SELECT receive_id, receive_status, receive_gold, end_time
                        FROM t_gold2receive
                        WHERE receive_id =:receive_id
                        AND user_id = :user_id
                        AND receive_gold = :receive_gold
                        AND receive_type = :receive_type';
                $historyInfo = $this->db->getRow($sql, array(
                   'receive_id' => $this->inputData['id'] ?? 0,
                   'user_id' => $this->userId,
                   'receive_gold' => $this->inputData['num'] ?? 0,
                   'receive_type' => $this->inputData['type'] ?? ''
                ));
                if (!$historyInfo) {
                    return new ApiReturn('', 205, '访问失败，请稍后再试');
                }
                if ($historyInfo['receive_status']) {
                    return new ApiReturn('', 401, '您已领取过该奖励');
                }
                if ($historyInfo['end_time'] && strtotime($historyInfo['end_time']) > time()) {
                    return new ApiReturn('', 403, '时间未到，请稍后再来领取');
                }
                $doubleStatus = $this->inputData['isDouble'] ?? 0;
                $updateStatus = $this->model->user2->updateGold(array(
                        'user_id' => $this->userId,
                        'gold' => $historyInfo['receive_gold'] * ($doubleStatus + 1),
                        'source' => $this->inputData['type'],
                        'type' => 'in',
                        'relation_id' => $historyInfo['receive_id']));
                //奖励金币成功
                if (TRUE === $updateStatus) {
                    $sql = 'UPDATE t_gold2receive SET receive_status = 1, is_double = ? WHERE receive_id = ?';
                    $this->db->exec($sql, $doubleStatus, $historyInfo['receive_id']);
                    
                    if (!in_array($this->inputData['type'], array('drink', 'lottery_count', 'clockin', 'clockin_count'))) {
                        $sql = 'SELECT COUNT(*) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ?';
                        $activityCount = $this->db->getOne($sql, $this->userId, $today, $this->inputData['type']);
                        if (!$activityInfo['activity_max'] || $activityCount < $activityInfo['activity_max']) {
                            $endDate = date('Y-m-d H:i:s', strtotime('+' . $activityInfo['activity_duration'] . 'minute'));
                            
                            $sql = 'SELECT COUNT(*) FROM t_award_config_update WHERE config_type = ?';
                            $updateConfig = $this->db->getOne($sql, $this->inputData['type']);
                            
                            $sql = 'SELECT MAX(withdraw_amount) FROM t_withdraw WHERE user_id = ? AND withdraw_status = "success"';
                            $withDraw = $this->db->getOne($sql, $this->userId);
                            if ($updateConfig && $withDraw) {
                                $sql = 'SELECT * FROM t_award_config_update WHERE config_type = ? AND (counter = 0 OR counter = ?) AND withdraw <= ? ORDER BY withdraw DESC';
                                $configInfo = $this->db->getRow($sql, $this->inputData['type'], $activityCount + 1, $withDraw);
                                $gold = rand($configInfo['award_min'], $configInfo['award_max']);
                            } else {
                                $sql = 'SELECT * FROM t_award_config WHERE config_type = ? AND counter_min = ?';
                                $configInfo = $this->db->getRow($sql, $this->inputData['type'], $activityCount + 1);
                                if ($configInfo) {
                                    $gold = rand($configInfo['award_min'], $configInfo['award_max']);
                                } else {
                                    $gold = rand($activityInfo['activity_award_min'], $activityInfo['activity_award_max']);
                                }
                            }
                            
                            $sql = 'INSERT INTO t_gold2receive SET user_id = ?, receive_date = ?, receive_type = ?, end_time = ?, receive_gold = ?';
                            $this->db->exec($sql, $this->userId, $today, $this->inputData['type'], $endDate, $gold);
                        }
                    }
                    
                    $goldInfo = $this->model->user2->getGold($this->userId);
                    return new ApiReturn(array('awardGold' => $historyInfo['receive_gold'] * ($doubleStatus + 1), 'currentGold' => $goldInfo['currentGold']));
                }
                return $updateStatus;
        }
    }
    
    /**
     * 申请提现接口
     * @return \ApiReturn
     */
    public function requestWithdrawalAction () {
        if (isset($this->inputData['amount']) && $this->inputData['amount']) {
            $withdrawalAmount = $this->inputData['amount'];
            $withdrawalGold = $this->inputData['amount'] * $this->withdrawalRate;
            //获取当前用户可用金币
            $userGoldInfo = $this->model->user2->getGold($this->userId);
            
            if ($withdrawalGold > $userGoldInfo['currentGold']) {
                return new ApiReturn('', 404, '抱歉，您的金币数暂未达到提现门槛');
            }
            //是否绑定支付宝
            $sql = 'SELECT alipay_account, alipay_name FROM t_user WHERE user_id = ?';
            $alipayInfo = $this->db->getRow($sql, $this->userId);
            if (isset($alipayInfo['alipay_account']) && $alipayInfo['alipay_account'] && isset($alipayInfo['alipay_name']) && $alipayInfo['alipay_name']) {
                //1元提现只能一次 to do
                if (1 == $withdrawalAmount) {
                    $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = 1 AND (withdraw_status = "pending" OR withdraw_status = "success")';
                    if ($this->db->getOne($sql, $this->userId)) {
                        return new ApiReturn('', 405, '新用户首次提现专享');
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
                return new ApiReturn('', 406, '请先绑定支付宝账户');
            }
        } else {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
    }
    
    /**
     * 申请提现接口 微信20200310
     * @return \ApiReturn
     */
    public function requestWithdrawalNewAction () {
        if (isset($this->inputData['amount']) && $this->inputData['amount']) {
            //是否绑定微信
            $sql = 'SELECT unionid, openid, umeng_token, user_status FROM t_user WHERE user_id = ?';
            $payInfo = $this->db->getRow($sql, $this->userId);
            if (!$payInfo['user_status']) {
                return new ApiReturn('', 408, '申请失败');
            }
            $umengApi = new Umeng();
            $umengReturn = $umengApi->verify($payInfo['umeng_token']);
            $withdrawalAmount = $this->inputData['amount'];
            $withdrawalGold = $this->inputData['amount'] * $this->withdrawalRate;
            if (TRUE !== $umengReturn && isset($umengReturn->suc) && TRUE === $umengReturn->suc && $umengReturn->score < 90) {
                //update user invild && insert request failed
                $sql = 'UPDATE t_user SET user_status = 0 WHERE user_id = ?';
                $this->db->exec($sql, $this->userId);
                $sql = 'INSERT INTO t_withdraw SET user_id = :user_id, 
                        withdraw_amount = :withdraw_amount, 
                        withdraw_gold = :withdraw_gold, 
                        withdraw_status = "failure", 
                        withdraw_method = "wechat",
                        wechat_openid = :wechat_openid,
                        withdraw_remark = :withdraw_remark';
                $this->db->exec($sql, array('user_id' => $this->userId,
                    'withdraw_amount' => $withdrawalAmount,
                    'withdraw_gold' => $withdrawalGold,
                    'wechat_openid' => $payInfo['openid'],
                    'withdraw_remark' => '友盟分值低于90分'));
                return new ApiReturn('', 408, '申请失败');
            }
            //获取当前用户可用金币
            $userGoldInfo = $this->model->user2->getGold($this->userId);
            
            if ($withdrawalGold > $userGoldInfo['currentGold']) {
                return new ApiReturn('', 404, '抱歉，您的金币数暂未达到提现门槛');
            }
            if (isset($payInfo['unionid']) && $payInfo['unionid'] && isset($payInfo['openid']) && $payInfo['openid']) {
                //1元提现只能一次 to do
                if (in_array($withdrawalAmount, array(1, 5))) {
                    $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = ? AND (withdraw_status = "pending" OR withdraw_status = "success")';
                    if ($this->db->getOne($sql, $this->userId, $withdrawalAmount)) {
                        return new ApiReturn('', 405, '新用户首次提现专享');
                    }
                }
                $sql = 'INSERT INTO t_withdraw SET user_id = :user_id, 
                        withdraw_amount = :withdraw_amount, 
                        withdraw_gold = :withdraw_gold, 
                        withdraw_status = "pending", 
                        withdraw_method = "wechat",
                        wechat_openid = :wechat_openid';
                $this->db->exec($sql, array('user_id' => $this->userId,
                    'withdraw_amount' => $withdrawalAmount,
                    'withdraw_gold' => $withdrawalGold, 
                    'wechat_openid' => $payInfo['openid']));
                return new ApiReturn('');
            } else {
                return new ApiReturn('', 407, '请先绑定微信账户');
            }
        } else {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
    }
    
    /**
     * 金币明细列表
     * @return \ApiReturn
     */
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
            } elseif ('newer_invalid' == $v['source']) {
                $v['gSource'] = '新手红包过期';
            }
            $v['gTime'] = strtotime($v['gTime']) * 1000;
        });
        return new ApiReturn($goldDetail);    
    }
    
    /**
     * 提现明细列表
     * @return \ApiReturn
     */
    public function withdrawDetailAction () {
        $statusArray = array('pending' => '审核中', 'success' => '审核成功', 'failure' => '审核失败');
        $sql = "SELECT withdraw_amount amount, withdraw_status status, create_time wTime, withdraw_method method  FROM t_withdraw WHERE user_id = ? ORDER BY withdraw_id DESC";
        $withdrawDetail = $this->db->getAll($sql, $this->userId);
        array_walk($withdrawDetail, function (&$v) use ($statusArray) {
            $v['status'] = $statusArray[$v['status']];
            $v['wTime'] = strtotime($v['wTime']) * 1000;
            $v['method'] = (('alipay' == $v['method']) ? '支付宝现金' : '微信现金');
        });
        return new ApiReturn($withdrawDetail);    
    }
    
    /**
     * 上传第三方错误
     * @return \ApiReturn
     */
    public function uploadErrorAction () {
        if (isset($this->inputData['versionCode']) && isset($this->inputData['errorSource']) && isset($this->inputData['errorCode'])) {
            $sql = 'INSERT INTO t_sdk_error SET user_id = ?, sdk_source = ?, version_id = ?, error_code = ?, adpos_id = ?';
            $this->db->exec($sql, $this->userId, $this->inputData['errorSource'], $this->inputData['versionCode'], $this->inputData['errorCode'], $this->inputData['adposId'] ?? '');
            return new ApiReturn();
        } else {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
    }

    /**
     * 获取最近7天步数
     * @return ApiReturn
     */
    public function walkCountListAction () {
        $startTime = strtotime('-6 day');
        $sql = 'SELECT walk_date, total_walk FROM t_walk WHERE user_id = ? AND walk_date >= ?';
        $walkInfo = $this->db->getPairs($sql, $this->userId, date('Y-m-d', $startTime));
        for ($i=0;$i<7;$i++) {
            $date = date('Y-m-d', $startTime);
            $return[] = array('walkTime' => $startTime * 1000, 'walkCount' => $walkInfo[$date] ?? 0);
            $startTime = strtotime('+1 day', $startTime);
        }
        return new ApiReturn($return);
    }
}