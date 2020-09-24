<?php

class User2Model extends AbstractModel {
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
                $score = $umengClass->verify($deviceInfo['umengToken']) ?: 0;
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
                $score = $umengClass->verify($deviceInfo['umengToken']) ?: 0;
            }
            $nickName = '游客' . substr($deviceId, -2) . date('Ymd');//游客+设备号后2位+用户激活日期
            $this->db->exec($sql, $deviceId, $nickName, $deviceInfo['source'] ?? '', $reyunAppName, $deviceInfo['VAID'] ?? '', $deviceInfo['AAID'] ?? '', $deviceInfo['OAID'] ?? '', $deviceInfo['brand'] ?? '', $deviceInfo['model'] ?? '', $deviceInfo['SDKVersion'] ?? '', $deviceInfo['AndroidId'] ?? '', $deviceInfo['IMEI'] ?? '', $deviceInfo['MAC'] ?? '', $invitedCode, $deviceInfo['umengToken'] ?? '', $score);
            $userId = $this->db->lastInsertId();
            
            $sql = 'SELECT activity_award_min, activity_status FROM t_activity WHERE activity_type = "newer"';
            $goldInfo = $this->db->getRow($sql);
            if (!$goldInfo['activity_status']) {
                $gold = 0;
            } else {
                $this->model->gold->updateGold(array('user_id' => $userId, 'gold' => $goldInfo['activity_award_min'], 'source' => 'newer', 'type' => 'in'));
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
     * 验证token有效性
     * @return ApiReturn
     */
    public function verifyToken() {
        $token = $_SERVER['HTTP_ACCESSTOKEN'] ?? '';
        if ($token) {
            $sql = 'SELECT user_id FROM t_user WHERE access_token = ?';
            $userId = $this->db->getOne($sql, $token);
            if ($userId) {
                return $userId;
            }
        }
        return new ApiReturn('', 201, '登录失败');
    }

    /**
     * 获取用户金币信息 总金币 冻结金币 当前金币
     * @param $userId
     * @return array
     */
    public function getGold ($userId) {
        //获取当前用户可用金币
        $sql = 'SELECT SUM(IF(change_type="in", change_gold, -change_gold)) FROM t_gold WHERE user_id = ?';
        $totalGold = $this->db->getOne($sql, $userId) ?? 0;
        $sql = 'SELECT SUM(withdraw_gold) FROM t_withdraw WHERE user_id = ? AND withdraw_status = "pending"';
        $bolckedGold = $this->db->getOne($sql, $userId) ?? 0;
        $currentGold = $totalGold - $bolckedGold;
        return array('totalGold' => $totalGold, 'bolckedGold' => $bolckedGold, 'currentGold' => $currentGold);
    }

    /**
     * 获取用户信息
     * @param $userId
     * @param string $filed
     * @return mixed
     */
    public function userInfo ($userId, $filed='') {
        $sql = 'SELECT * FROM t_user WHERE user_id = ?';
        $userInfo = $this->db->getRow($sql, $userId);
        return $userInfo[$filed] ?? $userInfo;
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

    public function reyunAppName ($imie, $oaid, $androidid) {
        $sql = 'SELECT app_name FROM t_reyun_log WHERE imei = ?';
        $appName = $this->db->getOne($sql, $imie);
        if ($appName) {
            return $appName;
        }
        $appName = $this->db->getOne($sql, $oaid);
        if ($appName) {
            return $appName;
        }
        $appName = $this->db->getOne($sql, $androidid);
        if ($appName) {
            return $appName;
        }
        return '';

    }
            
}