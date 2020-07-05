<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class UserModel extends AbstractModel {
    protected $maxGoldEveryDay = 3000;

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
                'isOneCashed' => $isOneCashed ? 1 : 0 
            );
        } else {
            $sql = 'INSERT INTO t_user SET device_id = ?, nickname = ?, app_name = ?, VAID = ?, AAID = ?, OAID = ?, brand = ?, model = ?, SDKVersion = ?, AndroidId = ?, IMEI = ?, MAC = ?';
            $nickName = '游客' . substr($deviceId, -2) . date('Ymd');//游客+设备号后2位+用户激活日期
            $this->db->exec($sql, $deviceId, $nickName, $deviceInfo['source'] ?? '', $deviceInfo['VAID'] ?? '', $deviceInfo['AAID'] ?? '', $deviceInfo['OAID'] ?? '', $deviceInfo['brand'] ?? '', $deviceInfo['model'] ?? '', $deviceInfo['SDKVersion'] ?? '', $deviceInfo['AndroidId'] ?? '', $deviceInfo['IMEI'] ?? '', $deviceInfo['MAC'] ?? '');
            $userId = $this->db->lastInsertId();
            $sql = 'SELECT activity_award_min FROM t_activity WHERE activity_type = "newer"';
            $gold = $this->db->getOne($sql);
            $this->updateGold(array('user_id' => $userId,
                'gold' => $gold,
                'source' => 'newer',
                'type' => 'in'));
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
                'award' =>$gold
            );
        }
    }
    /**
     * 
     * @param type $params
     * $params user_id
     * $params gold
     * $params source
     * $params type
     * $params relation_id if has
     * @return \ApiReturn
     */
    
    public function updateGold($params = array()) {
        $todayDate = date('Y-m-d');
        if ('in' == $params['type']) {
            $sql = 'SELECT SUM(change_gold) FROM t_gold WHERE user_id = ? AND change_type = "in" AND change_date = ? AND gold_source NOT IN ("newer", "phone", "wechat", "system")';
            $goldToday = $this->db->getOne($sql, $params['user_id'], $todayDate);
            if ($goldToday > $this->maxGoldEveryDay) {
                return new ApiReturn('', 202, '今日金币领取已达上限');
            }
        }
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
        return TRUE;
    }
    
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
    
    public function getGold ($userId) {
        //获取当前用户可用金币
        $sql = 'SELECT SUM(IF(change_type="in", change_gold, -change_gold)) FROM t_gold WHERE user_id = ?';
        $totalGold = $this->db->getOne($sql, $userId) ?? 0;
        $sql = 'SELECT SUM(withdraw_gold) FROM t_withdraw WHERE user_id = ? AND withdraw_status = "pending"';
        $bolckedGold = $this->db->getOne($sql, $userId) ?? 0;
        $currentGold = $totalGold - $bolckedGold;
        return array('totalGold' => $totalGold, 'bolckedGold' => $bolckedGold, 'currentGold' => $currentGold);
    }
    
    public function userInfo ($userId, $filed='') {
        $sql = 'SELECT * FROM t_user WHERE user_id = ?';
        $userInfo = $this->db->getRow($sql, $userId);
        return $userInfo[$filed] ?? $userInfo;
    }
}