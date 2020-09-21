<?php 

Class VideoController extends AbstractController {
    protected $userId;
    
    public function init() {
        parent::init();
        $userId = $this->model->user->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        $this->userId = $userId;
    }

    //视频列表接口 带翻页
    public function listAction() {
        if (isset($this->inputData['targetid']) && $this->inputData['targetid']) {

            $sql = 'SELECT * FROM t_xunfei_video_tree WHERE targetid = ?';
            if (!$this->db->getOne($sql, $this->inputData['targetid'])) {
                return new ApiReturn('', 205, '访问失败，请稍后再试');
            }
            $maxCount = 8;
            //传入类型id
            $sql = 'SELECT IFNULL(id, 0) FROM t_xunfei_user_record WHERE user_id = ? AND targetid = ?';
            $startId = $this->db->getOne($sql, $this->userId, $this->inputData['targetid']);

            //like_count likeCount
            $sql = 'SELECT id, name, url, cover_url coverUrl, targetid FROM t_xunfei_video_sub WHERE targetid = ? AND id > ? ORDER BY id LIMIT ' . $maxCount;
            $videoList = $this->db->getAll($sql, $this->inputData['targetid'], $startId);

            $videoCount = count($videoList);
            if ($videoCount) {
                if ($videoCount < $maxCount) {
                    $sql = 'SELECT id, name, url, cover_url coverUrl, targetid FROM t_xunfei_video_sub WHERE targetid = ? AND id > 0 ORDER BY id LIMIT ' . ($maxCount - $videoCount);
                    $videoList = array_merge($videoList, $this->db->getAll($sql, $this->inputData['targetid']));
                }
            } else {
                $sql = 'UPDATE t_xunfei_user_record SET id = 0 WHERE user_id = ? AND targetid = ?';
                $this->db->exec($sql, $this->userId, $this->inputData['targetid']);

                $sql = 'SELECT id, name, url, cover_url coverUrl, targetid FROM t_xunfei_video_sub WHERE targetid = ? AND id > 0 ORDER BY id LIMIT ' . $maxCount;
                $videoList = array_merge($videoList, $this->db->getAll($sql, $this->inputData['targetid']));
            }
            return new ApiReturn($videoList);
        }
        return new ApiReturn('', 205, '访问失败，请稍后再试');
    }

    //观看视频接口
    public function watchAction () {
        if (isset($this->inputData['targetid']) && $this->inputData['targetid'] && isset($this->inputData['id']) && $this->inputData['id']) {
            $sql = 'SELECT id FROM t_xunfei_user_record WHERE user_id = ? AND id = ? AND targetid = ?';
            $watchInfo = $this->db->exec($sql, $this->userId, $this->inputData['id'], $this->inputData['targetid']);
            if (($watchInfo && $this->inputData['id'] > $watchInfo['id']) || !$watchInfo) {
                $sql = 'REPLACE INTO t_xunfei_user_record SET user_id = ?, id = ?, targetid = ?';
                $this->db->exec($sql, $this->userId, $this->inputData['id'], $this->inputData['targetid']);
            }
            return new ApiReturn();
        }
        return new ApiReturn('', 205, '访问失败，请稍后再试');
    }

    //每日已领取的金币次数，最大金币次数，奖励数组[1,2,4,5,6],额外奖励
    public function awardAction () {
        $task = new Task();
        $awardInfo = $task->getTask('xunfei', $this->userId);
        $awardBonusInfo = $task->getTask('xunfei_bonus', $this->userId);
//        var_dump($awardInfo);
//        var_dump($awardBonusInfo);
//        exit;
        return new ApiReturn(array('todayReceiveCount' => $awardInfo['receiveCount'], 'maxCount' => $awardInfo['countMax'], 'award' => array('id' => $awardInfo['id'], 'type' => 'xunfei', 'num' => $awardInfo['num'], 'isReceive' => $awardInfo['isReceive']), 'bonusAward' => array('id' => $awardBonusInfo['id'], 'type' => 'xunfei_bonus', 'num' => $awardBonusInfo['num'], 'isReceive' => $awardBonusInfo['isReceive'])));
    }


}