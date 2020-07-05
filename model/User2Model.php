<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class User2Model extends UserModel {
    protected $maxGoldEveryDay = 20000;

    /**
     * 获取用户信息/添加新用户
     * @param type $deviceId
     * @param type $deviceInfo
     * @return type
     */
    public function getUserInfo($deviceId, $deviceInfo = array()) {
        $whereArr = $data = array();
        $whereArr[] = 1;
        $whereArr[] = 'device_id = :device_id';
        $data['device_id'] = $deviceId;
        
        $where = implode(' AND ', $whereArr);
        $sql = 'SELECT * FROM t_user WHERE ' . $where;
        $userInfo = $this->db->getRow($sql, $data);
        
        if ($userInfo) {
            $goldInfo = $this->getGold($userInfo['user_id']);
            if (isset($deviceInfo['umengToken']) && $deviceInfo['umengToken']) {
                $umengClass = new Umeng();
                $score = 0;
                $umengReturn = $umengClass->verify($deviceInfo['umengToken']);
                if (TRUE !== $umengReturn && isset($umengReturn->suc) && TRUE === $umengReturn->suc) {
                    $score = $umengReturn->score;
                }
                $sql = 'UPDATE t_user SET umeng_token = ?, umeng_score = ? WHERE user_id = ?';
                $this->db->exec($sql, $deviceInfo['umengToken'], $score, $userInfo['user_id']);
            }
            $sql = 'SELECT COUNT(withdraw_id) FROM t_withdraw WHERE withdraw_amount = 1 AND user_id = ? AND withdraw_status = "success"';
            $isOneCashed = $this->db->getOne($sql, $userInfo['user_id']);
            return  array(
                'userId' => $userInfo['user_id'],
                'accessToken' => $userInfo['access_token'],
                'currentGold' => $goldInfo['currentGold'],
                'nickname' => $userInfo['nickname'],
                'sex' => $userInfo['sex'],
                'province' => $userInfo['province'],
                'city' => $userInfo['city'],
                'country' => $userInfo['country'],
                'headimgurl' => $userInfo['headimgurl'],
                'phone' => $userInfo['phone_number'],
                'isOneCashed' => $isOneCashed ? 1 : 0,
                'invitedCode' => $userInfo['invited_code']
            );
        } else {
            $invitedClass = new Invited();
            $invitedCode = $invitedClass->createCode();
            $sql = 'SELECT app_name FROM t_reyun_log WHERE imei = ?';
            $reyunAppName = $this->db->getOne($sql, $deviceInfo['IMEI'] ?? '') ?: '';
            $sql = 'INSERT INTO t_user SET device_id = ?, nickname = ?, app_name = ?, reyun_app_name = ?,  VAID = ?, AAID = ?, OAID = ?, brand = ?, model = ?, SDKVersion = ?, AndroidId = ?, IMEI = ?, MAC = ?, invited_code = ?, umeng_token = ?, umeng_score = ?';
            $score = 0;
            if (isset($deviceInfo['umengToken']) && $deviceInfo['umengToken']) {
                $umengClass = new Umeng();
                $umengReturn = $umengClass->verify($deviceInfo['umengToken']);
                if (TRUE !== $umengReturn && isset($umengReturn->suc) && TRUE === $umengReturn->suc) {
                    $score = $umengReturn->score;
                }
            }
            $nickName = '游客' . substr($deviceId, -2) . date('Ymd');//游客+设备号后2位+用户激活日期
            $this->db->exec($sql, $deviceId, $nickName, $deviceInfo['source'] ?? '', $reyunAppName, $deviceInfo['VAID'] ?? '', $deviceInfo['AAID'] ?? '', $deviceInfo['OAID'] ?? '', $deviceInfo['brand'] ?? '', $deviceInfo['model'] ?? '', $deviceInfo['SDKVersion'] ?? '', $deviceInfo['AndroidId'] ?? '', $deviceInfo['IMEI'] ?? '', $deviceInfo['MAC'] ?? '', $invitedCode, $deviceInfo['umengToken'] ?? '', $score);
            $userId = $this->db->lastInsertId();
            
            $sql = 'SELECT activity_award_min, activity_status FROM t_activity WHERE activity_type = "newer"';
            $goldInfo = $this->db->getRow($sql);
            if (!$goldInfo['activity_status']) {
                $gold = 0;
            } else {
                $this->updateGold(array('user_id' => $userId,
                    'gold' => $goldInfo['activity_award_min'],
                    'source' => 'newer',
                    'type' => 'in'));
                $gold = $goldInfo['activity_award_min'];
            }
            
            $accessToken = md5($userId . time());
            $sql = 'UPDATE t_user SET
                    access_token = ?
                    WHERE user_id = ?';
            $this->db->exec($sql, $accessToken, $userId);
            return  array(
                'userId' => $userId,
                'accessToken' => $accessToken,
                'currentGold' => $gold,
                'nickname' => $nickName,
                'award' =>$gold,
                'invitedCode' => $invitedCode
            );
        }
    }
    
    /**
     * 更新用户金币
     * @param type $params
     * @return boolean|\ApiReturn
     */
    public function updateGold($params = array()) {
        $todayDate = date('Y-m-d');
        $userState = $this->userInfo($params['user_id'], 'user_status');
        if (!$userState) {
            return new ApiReturn('', 203, '抱歉您的账户已被冻结');
        }
        if ('in' == $params['type']) {
            $notInEveryTotal = array("newer", "wechat", "system", "invited_count", 'invited', 'do_invite');
            $sql = 'SELECT SUM(change_gold)
                    FROM t_gold
                    WHERE user_id = ?
                    AND change_type = "in"
                    AND change_date = ?
                    AND gold_source NOT IN ("' . implode('", "', $notInEveryTotal) .'")';
            $goldToday = $this->db->getOne($sql, $params['user_id'], $todayDate);
            if ($goldToday > $this->maxGoldEveryDay) {
                return new ApiReturn('', 202, '抱歉您已达到今日金币获取上限');
            }
        }
        if ('sign' == $params['type']) {
            $sql = "INSERT INTO t_gold SET
                    user_id = :user_id,
                    change_gold = :change_gold,
                    gold_source = :gold_source,
                    change_type = :change_type,
                    relation_id = :relation_id,
                    change_date = :change_date";
            $this->db->exec($sql, array(
                'user_id' => $params['user_id'],
                'change_gold' => $params['gold'],
                'gold_source' => $params['source'],
                'change_type' => $params['type'],
                'relation_id' => $params['relation_id'] ?? 0,
                'change_date' => $todayDate
            ));
        } else {
            $sql = "INSERT INTO t_gold (user_id, change_gold, gold_source, change_type, relation_id, change_date) 
                    SELECT :user_id, :change_gold, :gold_source, :change_type, :relation_id, :change_date
                    WHERE NOT EXISTS(
                    SELECT * 
                    FROM t_gold 
                    WHERE user_id = :user_id 
                    AND change_gold = :change_gold 
                    AND gold_source = :gold_source 
                    AND change_type = :change_type
                    AND relation_id = :relation_id
                    AND change_date = :change_date)";
            $this->db->exec($sql, array(
                'user_id' => $params['user_id'],
                'change_gold' => $params['gold'],
                'gold_source' => $params['source'],
                'change_type' => $params['type'],
                'relation_id' => $params['relation_id'] ?? 0,
                'change_date' => $todayDate
            ));
        }
        
        return TRUE;
    }
    
    /**
     * 更新用户每日首次登陆时间
     * @param type $userId
     */
    public function todayFirstLogin ($userId) {
        $sql = 'INSERT IGNORE INTO t_user_first_login SET date = ?, user_id = ?';
        $this->db->exec($sql, date('Y-m-d'), $userId);
    }
         
    /**
     * 更新用户最后登陆时间
     * @param type $userId
     */
    public function lastLogin ($userId) {
        $sql = 'UPDATE t_user SET last_login_time = ?, login_ip = ? WHERE user_id = ?';
        $this->db->exec($sql, date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? '', $userId);
    }
            
            
}