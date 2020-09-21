<?php

Class Activity3Controller extends Activity2Controller {

    /**
     *  步数挑战赛 20200804
     *  获取步数挑战赛信息
     * @return ApiReturn
     */
    public function contestAction() {
        $todayDate = date("Y-m-d");
        $tomorrowDate = date("Y-m-d", strtotime('+1 day'));
        $yesterdayDate = date("Y-m-d", strtotime('-1 day'));
        $return = array();
        $awardConfig = array(3000 => 20, 5000 => 500, 10000 => 1000);
        $todayWalks = NULL;

        foreach (array(3000, 5000, 10000) as $walks) {
            // 查看是否报名今日活动
            $sql = 'SELECT * FROM t_walk_contest LEFT JOIN t_walk_contest_user USING(contest_id) WHERE contest_date = ? AND user_id = ? AND contest_level = ?';
            $todayContest = $this->db->getRow($sql, $todayDate, $this->userId, $walks);
            $sql = 'SELECT * FROM t_walk_contest WHERE contest_date = ? AND contest_level = ?';
            $tomorrowContest = $this->db->getRow($sql, $tomorrowDate, $walks);

            $sql = 'SELECT COUNT(id) FROM t_walk_contest_user WHERE user_id = ? AND contest_id = ?';
            $isNextReg = $this->db->getOne($sql, $this->userId, $tomorrowContest['contest_id']) ? 1 : 0;
            // 查询明日活动
            if ($todayContest) {
                if (NULL === $todayWalks) {
                    $sql = 'SELECT total_walk FROM t_walk WHERE user_id = ? AND walk_date = ?';
                    $todayWalks = $this->db->getOne($sql, $this->userId, $todayDate) ?? 0;
                }
                //报名今天
                //  期数 名称 periods 预计可获得金币 expectedAward 当前达标 completeCount 总奖池 totalAward 当前步数 currentWalks 目标步数 targetWalks 当前时间 currentTime 结束时间 endTime 下期是否已报名 isNextReg 下期期数 名称 nextPeriods 下期报名人数 nextRegCount 下期奖池 nextTotalAward 下期活动id nextId
                //	下期的报名费用 nextRegFee // todo
                //	下期最低奖励 nextMinAward // todo
                $return[$walks]['current'] = array('periods' => $todayContest['contest_periods'], 'expectedReward' => $todayContest['complete_count'] ? ceil($todayContest['total_count'] * $awardConfig[$walks] / $todayContest['complete_count']) : 0, 'completeCount' => $todayContest['complete_count'], 'totalAward' => $todayContest['total_count'] * $awardConfig[$walks], 'currentWalks' => $todayWalks, 'targetWalks' => $walks, 'currentTime' => time() * 1000, 'endTime' => strtotime($todayContest['contest_date'] . ' 23:59:59') * 1000, 'isNextReg' => $isNextReg, 'nextPeriods' => $tomorrowContest['contest_periods'], 'nextRegCount' => $tomorrowContest['total_count'], 'nextTotalAward' => $tomorrowContest['total_count'] * $awardConfig[$walks], 'nextId' => $tomorrowContest['contest_id']);
            } else {
                //  期数名称 periods
                //	总奖池 totalAward
                //	报名人数 regCount
                //	当前时间 currentTime
                //	开始时间 startTime
                //	是否已报名 isReg
                //	报名费用 regFee // todo
                //	最低奖励 minAward // todo
                //  活动id id
                //未报名今天
                $return[$walks]['next'] = array('periods' => $tomorrowContest['contest_periods'], 'totalAward' => $tomorrowContest['total_count'] * $awardConfig[$walks], 'regCount' => $tomorrowContest['total_count'], 'currentTime' => time() * 1000, 'startTime' => strtotime($tomorrowContest['contest_date'] . '00:00:00') * 1000, 'isReg' => $isNextReg, 'id' => $tomorrowContest['contest_id']);
            }
        }

        // 昨日活动奖励
        $sql = 'SELECT * FROM t_walk_contest c LEFT JOIN t_walk_contest_user cu ON cu.contest_id = c.contest_id WHERE c.contest_date = ? AND user_id = ?';
        $yesterdayList = $this->db->getAll($sql, $yesterdayDate, $this->userId);
        $type = $yesterdayList ? 1 : 0;
        $receiveInfo = array();
        foreach ($yesterdayList as $info) {
            if ($info['is_complete']) {
                $sql = 'SELECT receive_id id, receive_gold num, receive_type type, receive_status isReceived FROM t_gold2receive WHERE user_id = ? AND receive_walk = ? AND receive_type = ? AND receive_date = ?';
                $receiveInfo[$info['contest_level']] = $this->db->getRow($sql, $this->userId, $info['contest_level'], 'walk_contest', date('Y-m-d'));
                $type = 2;
            }
        }
        // type  无弹窗 弹窗未达标 弹窗可领取
        $return['award'] = array('type' => $type, 'awardList' => $receiveInfo);
        return new ApiReturn($return);
    }

    /**
     * 用户报名步数挑战赛
     * @return ApiReturn
     */
    public function regContestAction () {
        $sql = 'SELECT * FROM t_walk_contest WHERE contest_id = ? AND contest_date = ?';
        $contestInfo = $this->db->getRow($sql, $this->inputData['id'] ?? 0, date('Y-m-d', strtotime('+1 day')));
        if (!$contestInfo) {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }

        $sql = 'SELECT COUNT(id) FROM t_walk_contest_user WHERE contest_id = ? AND user_id = ?';
        $regInfo = $this->db->getOne($sql, $contestInfo['contest_id'], $this->userId);
        if ($regInfo) {
            return new ApiReturn('', 601, '不能重复报名');
        }
        $regFee = array(5000 => 500, 10000 => 1000);
        if (in_array($contestInfo['contest_level'], array_keys($regFee))) {
            $goldInfo = $this->model->user3->getGold($this->userId);
            if ($goldInfo['currentGold'] < $regFee[$contestInfo['contest_level']]) {
                return new ApiReturn('', 602, '抱歉，您当前金币不足！');
            }
            $sql = 'INSERT INTO t_walk_contest_user SET contest_id = ?, user_id = ?';
            $this->db->exec($sql, $contestInfo['contest_id'], $this->userId);

            $this->model->gold->updateGold(array( 'user_id' => $this->userId, 'gold' => $regFee[$contestInfo['contest_level']], 'source' => 'walk_contest_regfee', 'type' => 'out', 'relation_id' => $this->db->lastInsertId()));

            $goldInfo = $this->model->user3->getGold($this->userId);
            return new ApiReturn(array('periods' => $contestInfo['contest_periods'], 'currentGold' => $goldInfo['currentGold']));
        }
        $sql = 'INSERT INTO t_walk_contest_user SET contest_id = ?, user_id = ?';
        $this->db->exec($sql, $contestInfo['contest_id'], $this->userId);

        // 20 是报名3000档位 步数挑战赛的奖励
        $goldId = $this->model->goldReceive->insert(array('user_id' => $this->userId, 'gold' => 20, 'type' => 'walkcontest_regaward'));

        $goldInfo = $this->model->user3->getGold($this->userId);
        return new ApiReturn(array('periods' => $contestInfo['contest_periods'], 'id' => $goldId, 'num' => 20, 'type' => 'walkcontest_regaward', 'currentGold' => $goldInfo['currentGold']));
    }

    /**
     * 步数挑战赛参赛记录
     * @return ApiReturn
     */
    public function contestRecordAction () {
        $awardConfig = array(3000 => 20, 5000 => 500, 10000 => 1000);
        // 获取的总金币 最大的奖励
        $award = $this->model->gold->walkContestTotal($this->userId);
        // 参与次数
        $sql = 'SELECT COUNT(id) FROM t_walk_contest_user WHERE user_id = ?';
        $totalReg = $this->db->getOne($sql, $this->userId);
        // 最大步数
        $sql = 'SELECT IFNULL(MAX(w.total_walk), 0) FROM t_walk_contest_user wu LEFT JOIN t_walk_contest wc ON wu.contest_id = wc.contest_id LEFT JOIN t_walk w ON wu.user_id = w.user_id AND wc.contest_date = w.walk_date WHERE wu.user_id = ?';
        $maxWalk = $this->db->getOne($sql, $this->userId);
        // 最近7天活动参与记录
        $sql = 'SELECT contest_periods, contest_level, complete_count, total_count, contest_date, is_complete FROM t_walk_contest_user wu LEFT JOIN t_walk_contest wc ON wu.contest_id = wc.contest_id WHERE wc.contest_date >= ? AND wu.user_id = ?';
        $contestList = $this->db->getAll($sql, date('Y-m-d', strtotime('-7 days')), $this->userId);
        $list = array();
        foreach ($contestList as $contestInfo) {
            $temp = array();
            $temp['periods'] = $contestInfo['contest_periods'];
            $temp['level'] = $contestInfo['contest_level'];
            if (strtotime($contestInfo['contest_date']) > time()) {
                // 0未开始
                $temp['status'] = 0;
            } else {
                // 1未达标 2已达标
                $temp['status'] = $contestInfo['is_complete'] ? 2 : 1;
            }
            $temp['completeCount'] = $contestInfo['complete_count'];
            $temp['totalAward'] = $contestInfo['total_count'] * $awardConfig[$contestInfo['contest_level']];
            $temp['award'] = $contestInfo['complete_count'] ? ceil($temp['totalAward'] / $contestInfo['complete_count']) : 0;
            $list[] = $temp;
        }
        return new ApiReturn(array('totalAward' => $award['total'], 'maxAward' => $award['max'], 'totalReg' => $totalReg, 'maxWalk' => $maxWalk, 'contestList' => $list));
    }

    public function threeAwardAction() {
        if (!isset($this->inputData['type']) || !isset($this->inputData['duration']) || !in_array($this->inputData['type'], array('baidu_news', 'baidu_video'))) {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
        $return = array();
        if ($this->inputData['duration'] > 60) {
            $sql = 'SELECT COUNT(*) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ?';
            $todayCount = $this->db->getOne($sql, $this->userId, date('Y-m-d'), $this->inputData['type']);
            if ($todayCount < 3) {
                $gold = $this->inputData['duration'] > 600 ? 200 : 100;
                $goldId = $this->model->goldReceive->insert(array('user_id' => $this->userId, 'gold' => $gold, 'type' => $this->inputData['type']));
                $return = array('id' => $goldId, 'num' => $gold, 'type' => $this->inputData['type']);
            }
        }
        return new ApiReturn($return);
    }
}

