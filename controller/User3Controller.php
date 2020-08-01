<?php 

Class User3Controller extends User2Controller {
    
    /**
     * 获取用户信息
     * 301 无效设备号
     * @return \ApiReturn
     */
    public function infoAction() {
        if (isset($this->inputData['deviceId'])) {
            $userInfo = $this->model->user3->getUserInfo($this->inputData['deviceId'], $this->inputData['userDeviceInfo'] ?? array());
            if (isset($this->inputData['userDeviceInfo']['source']) && isset($this->inputData['userDeviceInfo']['versionCode'])) {
                $sql = 'SELECT ad_status FROM t_version_ad WHERE version_id = ? AND app_name = ?';
                $userInfo['adStatus'] = $this->db->getOne($sql, $this->inputData['userDeviceInfo']['versionCode'], $this->inputData['userDeviceInfo']['source']) ?: 0;
            } else {
                $userInfo['adStatus'] = 0;
            }
            $this->model->user2->todayFirstLogin($userInfo['userId']);
            $this->model->user2->lastLogin($userInfo['userId']);
            unset($userInfo['userId']);
            return new ApiReturn($userInfo);
        } else {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
    }


    /**
     * 获取运营位列表
     * @return \ApiReturn|\apiReturn
     */
    public function getAdAction() {
        $userId = $this->model->user2->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        //start 首页底部  top 任务页 头部  new 任务页新手任务  daily 任务页日常任务  my 我的页面 右上角  dogs 我的页面狗狗世界导流
        $adCount = array('start' => 3, 'top' => 4, 'new' => 0, 'daily' => 0, 'my' => 1, 'dogs' => 0, 'start_2' => 3, 'start_left' => 1, 'task_h' => 0);
        if (!isset($this->inputData['location']) || !in_array($this->inputData['location'], array_keys($adCount)) || !isset($this->inputData['versionCode'])) {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
        $sql = 'SELECT advertise_id, advertise_type, advertise_name, advertise_subtitle, CONCAT(?, advertise_image) img, advertise_url, advertise_validity_type, advertise_validity_type, advertise_validity_start, advertise_validity_end, advertise_validity_length
                FROM t_advertise
                WHERE advertise_location = ?
                AND advertise_status = 1
                AND advertise_version <= ?
                ORDER BY advertise_version, advertise_sort DESC';
        $advertiseList = $this->db->getAll($sql, HOST_OSS, $this->inputData['location'], $this->inputData['versionCode']);
        $returnList = $tempArr = array();
        $adLimitCount = $adCount[$this->inputData['location']];
        $todayTime = time();
        $taskClass = new Task();
        $userCreateTime = $this->model->user2->userInfo($userId, 'create_time');
        $isAllBuild = 'new' == $this->inputData['location'] ? TRUE : FALSE;
        foreach ($advertiseList as $advertiseInfo) {
            if ($adLimitCount && $adLimitCount <= count($returnList)) {
                break;
            }
            switch ($advertiseInfo['advertise_validity_type']) {
                case 'fixed':
                    if (($todayTime < strtotime($advertiseInfo['advertise_validity_start'] . ' 00:00:00')) || ($todayTime > strtotime($advertiseInfo['advertise_validity_end'] . ' 23:59:59'))) {
                        continue 2;
                    }
                    break;
                case 'limited':
                    $adEndTime = strtotime('+ ' . $advertiseInfo['advertise_validity_length'] . 'days', strtotime($userCreateTime));
                    if ($adEndTime < $todayTime) {
                        continue 2;
                    }
                    break;
            }
            if (in_array($advertiseInfo['advertise_id'], array(2, 10, 24))) {
                $sql = 'SELECT access_token FROM t_user WHERE user_id = ?';
                $accessToken = $this->db->getOne($sql, $userId);
                $advertiseInfo['advertise_url'] .= '&userId=' . $accessToken;
            }
            $tempArr = array('type' => $advertiseInfo['advertise_type'],
                'name' => $advertiseInfo['advertise_name'],
                'subName' => $advertiseInfo['advertise_subtitle'],
                'img' => $advertiseInfo['img'],
                'url' => $advertiseInfo['advertise_url']);
            if ('task' == $advertiseInfo['advertise_type']) {
                $tempArr['info'] = $taskClass->getTask($advertiseInfo['advertise_url'], $userId);
                if ($isAllBuild) {
                    $isAllBuild = $tempArr['info']['isBuild'] ? TRUE : FALSE;
                }
            }
            $returnList[] = $tempArr;
        }
        if ($isAllBuild) {
            $returnList = [];
        }
        return new ApiReturn($returnList);
    }
}