<?php

class WalkCounter2 extends WalkCounter
{
    //领取奖励条件步数
    protected $rewardCounter = 50;
    protected $walkAwardLimitTime = 10;//限制领取步数奖励间隔
    protected $walkAwardLimitCount = 10;//限制领取步数奖励个数
    //阶段奖励规则
    protected $stageReward = array(
        1000 => 10,
        3000 => 30,
        5000 => 50,
        10000 => 80);
    protected $userId;
    protected $stepCount;
    protected $todayDate;

    /**
     * Constructor
     */
    public function __construct($userId, $stepCount = 0)
    {
        $this->userId = $userId;
        $this->stepCount = $stepCount;
        $this->todayDate = date('Y-m-d');
        $this->setStageReward();
        $this->calculationReward();
        //插入步数奖励待领取
        $this->__insertWalkAward();
    }
    
    public function getReturnInfo ($type) {
        switch ($type) {
            case 'walk':
                $sql = 'SELECT COUNT(gr.receive_id) count, MIN(g.create_time) min
                    FROM t_gold2receive gr
                    LEFT JOIN t_gold g ON gr.receive_id = g.relation_id AND g.gold_source = gr.receive_type
                    WHERE gr.user_id = ? 
                    AND gr.receive_date = ? 
                    AND gr.receive_type = "walk" 
                    AND gr.receive_status = 1
                    AND g.create_time >= ?';
                $receivedInfo = $this->db->getRow($sql, $this->userId, $this->todayDate, date('Y-m-d H:i:s', strtotime('-' . $this->walkAwardLimitTime . ' minutes')));
                if ($this->walkAwardLimitCount <= $receivedInfo['count']) {
                    $return['list'] = array();
                    $return['getTime'] = strtotime('+' . $this->walkAwardLimitTime . ' minutes', strtotime($receivedInfo['min'])) * 1000;
                    $return['serverTime'] = time() * 1000;
                } else {
                    $sql = 'SELECT receive_id id, receive_gold num, receive_type type 
                        FROM t_gold2receive 
                        WHERE user_id = ? 
                        AND receive_date = ? 
                        AND receive_type = "walk" 
                        AND receive_status = 0 
                        ORDER BY receive_id LIMIT ' . ($this->walkAwardLimitCount - $receivedInfo['count']);
                    $return['list'] = $this->db->getAll($sql, $this->userId, $this->todayDate);
                }
                $sql = 'SELECT COUNT(*)
                        FROM t_gold2receive 
                        WHERE user_id = ? 
                        AND receive_date = ? 
                        AND receive_type = "walk"
                        AND receive_status = 1';
                $return['restCount'] = floor($this->stepCount / $this->rewardCounter) - $this->db->getOne($sql, $this->userId, $this->todayDate);
                return $return;
            case 'walk_stage':
                return $this->__walkStageList();
        }
    }
    
    public function verifyReceive ($data) {
        $sql = 'SELECT receive_id, receive_status
                FROM t_gold2receive
                WHERE receive_id =:receive_id
                AND user_id = :user_id
                AND receive_gold = :receive_gold
                AND receive_type = :receive_type
                AND receive_date = :receive_date';
        return $this->db->getRow($sql, array(
           'receive_id' => $data['receive_id'],
           'user_id' => $this->userId,
           'receive_gold' => $data['receive_gold'],
           'receive_type' => $data['receive_type'],
           'receive_date' => $this->todayDate,
        ));
    }
    
    public function receiveSuccess ($receiveId, $doubleStatus) {
        $sql = 'UPDATE t_gold2receive SET receive_status = 1, is_double = ? WHERE receive_id = ?';
        $this->db->exec($sql, $doubleStatus, $receiveId);
    }
    
    public function getStepCount() {
        return $this->stepCount;
    }
    
    protected function calculationReward() {
        $sql = 'SELECT total_walk, walk_id FROM t_walk WHERE user_id = :user_id AND walk_date = :walk_date';
        $walkInfo = $this->db->getRow($sql, array('user_id' => $this->userId, 'walk_date' => $this->todayDate));
        if ($walkInfo) {
            if ($this->stepCount < $walkInfo['total_walk']) {
                $this->stepCount = $walkInfo['total_walk'];
                return FALSE;
            } else {
                $sql = 'UPDATE t_walk SET total_walk = ? WHERE walk_id = ?';
                $this->db->exec($sql, $this->stepCount, $walkInfo['walk_id']);
            }
        } else {
            $sql = 'INSERT INTO t_walk SET total_walk = ?, user_id = ?, walk_date = ?';
            $this->db->exec($sql, $this->stepCount, $this->userId, $this->todayDate);
        }
        
        //插入阶段步数奖励待领取
        $sql = 'SELECT MAX(receive_walk) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = "walk_stage"';
        $stageStep = $this->db->getOne($sql, $this->userId, $this->todayDate) ?: 0;
        foreach ($this->stageReward as $step => $gold) {
            if ($step > $this->stepCount) {
                break;
            }
            if ($step <= $stageStep) {
                continue;
            }
            $sql = 'INSERT INTO t_gold2receive (user_id, receive_date, receive_gold, receive_walk, receive_type) 
                    SELECT :user_id, :receive_date, :receive_gold, :receive_walk, :receive_type FROM DUAL
                    WHERE NOT EXISTS(SELECT receive_id FROM t_gold2receive WHERE user_id = :user_id
                AND receive_date = :receive_date
                AND receive_walk = :receive_walk
                AND receive_type = :receive_type)';
            $this->db->exec($sql, array(
                'user_id' => $this->userId,
                'receive_walk' => $step, 
                'receive_date' => $this->todayDate, 
                'receive_gold' => $gold,
                'receive_type' => 'walk_stage'));
        }
    }
    
    protected function __walkStageList () {
        $sql = 'SELECT receive_id id, receive_gold num, receive_type type, receive_walk, receive_status isReceived FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = "walk_stage"';
        $awradList = $this->db->getAll($sql, $this->userId, $this->todayDate);
        $i = 0;
        $walkList = array();
        foreach ($this->stageReward as $walk => $award) {
            $awardInfo = $awradList[$i] ?? array();
            $walkList[] = array_merge($awardInfo, array('stageWalk' => $walk, 'stageAward' => $award));
            $i++;
        }
        return $walkList;
    }
    
    protected function setStageReward () {
        $sql = 'SELECT counter_min, award_min FROM t_award_config WHERE config_type = ? ORDER BY counter_min ASC';
        $this->stageReward = $this->db->getPairs($sql, 'walk_stage');
    }

    /**
     * 插入步数奖励待领取
     */
    protected function __insertWalkAward () {
        $sql = 'SELECT MAX(receive_walk) max, COUNT(receive_id) count FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = "walk"';
        $receiceStep = $this->db->getRow($sql, $this->userId, $this->todayDate);
        $residualStep = $this->stepCount - $receiceStep['max'];
        $count = $receiceStep['count'];
        $sql = 'SELECT COUNT(*) FROM t_award_config_update WHERE config_type = ?';
        $updateConfig = $this->db->getOne($sql, 'walk');
        $sql = 'SELECT MAX(withdraw_amount) FROM t_withdraw WHERE user_id = ? AND withdraw_status = "success"';
        $withDraw = $this->db->getOne($sql, $this->userId);

        $sql = 'SELECT COUNT(receive_id) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = "walk" AND receive_status = 0';
        $notReceiveCount = $this->db->getOne($sql, $this->userId, $this->todayDate);

        while ($residualStep >= $this->rewardCounter) {
            if ($notReceiveCount >= 5) {
                break;
            }
            $count++;
            if ($updateConfig && $withDraw) {
                $sql = 'SELECT award_min, award_max FROM t_award_config_update WHERE config_type = "walk" AND counter <= ? AND withdraw <= ? ORDER BY withdraw DESC, counter DESC';
                $awardRange = $this->db->getRow($sql, $count, $withDraw);
            } else {
                $sql = 'SELECT award_min, award_max FROM t_award_config WHERE config_type = "walk" AND counter_min <= ? ORDER BY counter_min DESC';
                $awardRange = $this->db->getRow($sql, $count);
            }

            $sql = 'INSERT INTO t_gold2receive (user_id, receive_date, receive_gold, receive_walk, receive_type) 
                    SELECT :user_id, :receive_date, :receive_gold, :receive_walk, :receive_type FROM DUAL
                    WHERE NOT EXISTS(SELECT receive_id FROM t_gold2receive WHERE user_id = :user_id
                AND receive_date = :receive_date
                AND receive_walk = :receive_walk
                AND receive_type = :receive_type)';
            $this->db->exec($sql, array(
                'user_id' => $this->userId,
                'receive_walk' => $this->rewardCounter * $count,
                'receive_date' => $this->todayDate,
                'receive_gold' => rand($awardRange['award_min'], $awardRange['award_max']),
                'receive_type' => 'walk'));
            $notReceiveCount++;
            $residualStep -= $this->rewardCounter;
        }
    }
}