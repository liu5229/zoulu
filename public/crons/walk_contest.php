<?php

// 步数挑战赛数据统计结算 20200731
//  一小时执行一次
require_once __DIR__ . '/../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$model = new Model();
$awardConfig = array(3000 => 30, 5000 => 500, 10000 => 1000);
$virtualConfig = array(3000 => array(array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250)), 5000 => array(array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150)), 10000 => array(array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150)));

// 获取参数 是每天第几次执行
$variableName = 'contest_' . date('Ymd');
$sql = 'SELECT variable_value FROM t_variable WHERE variable_name = ?';
$execCount = $db->getOne($sql, $variableName) ?: 0;


// 没有参数的时候需要执行 前一天的数据
if (!$execCount) {
    updateData(date('Y-m-d', strtotime('-1 day')), 24);
    $sql = 'SELECT * FROM t_walk_contest WHERE contest_date = ?';
    $contestList = $db->getAll($sql, date('Y-m-d', strtotime('-1 day')));
    //执行前一天的奖励发放工作
    foreach ($contestList as $contestInfo) {
        $sql = 'SELECT user_id FROM t_walk_contest_user WHERE is_complete = 1 AND contest_id = ?';
        $realCompleteUser = $db->getColumn($sql, $contestInfo['contest_id']);
        if ($realCompleteUser) {
            // 参与的用户+参与的虚拟用户 total
            // 完成的用户+完成的虚拟用户 complete
            // 计算每个完成的用户奖励 total * 档位的奖励 / complete 向上取整
            $award = $contestInfo['complete_count'] ? ceil($contestInfo['total_count'] * $awardConfig[$contestInfo['contest_level']] / $contestInfo['complete_count']) : 0;
            //发放奖励
            foreach ($realCompleteUser as $userId) {
                $data[] = array($userId, $award, $contestInfo['contest_level'], '\'walk_contest\'', date('Y-m-d'));
            }
            $model->goldReceive->batchInsert($data);
        }
    }
}

//更新今天的数据
updateData(date('Y-m-d'), $execCount);

if (4 == $execCount) {
    // 每天定时新增 后天的挑战赛数据  挑战赛分为三档
    $addTime = strtotime('+2day');
    $periods = date('md', $addTime);
    $contestDate = date('Y-m-d', $addTime);

    $sql = 'INSERT INTO t_walk_contest (contest_periods, contest_level, contest_date) VALUE (:contest_periods, 3000, :contest_date), (:contest_periods, 5000, :contest_date), (:contest_periods, 10000, :contest_date)';
    $db->exec($sql, array('contest_periods' => $periods, 'contest_date' => $contestDate));
}

$sql = 'REPLACE INTO t_variable SET variable_name = ?, variable_value = ?';
$db->exec($sql, $variableName, $execCount + 1);
echo 'done';

function updateData($date, $count) {
    global $db, $virtualConfig;
    $sql = 'SELECT * FROM t_walk_contest WHERE contest_date = ?';
    $contestList = $db->getAll($sql, $date);

    foreach ($contestList as $contestInfo) {
        // 添加  当前 完成用户
        $sql = 'SELECT c.id, c.user_id, c.is_complete, w.total_walk FROM t_walk_contest_user c LEFT JOIN t_walk w ON c.user_id = w.user_id  WHERE w.walk_date = ? AND contest_id = ?';
        $userList = $db->getAll($sql, $date, $contestInfo['contest_id']);
        $completeUserCount = 0;
        foreach ($userList as $userInfo) {
            if ($userInfo['is_complete']) {
                $completeUserCount++;
            } elseif ($userInfo['total_walk'] > $contestInfo['contest_level']) {
                $completeUserCount++;
                $sql = 'UPDATE t_walk_contest_user SET is_complete = 1 WHERE id = ?';
                $db->exec($sql, $userInfo['id']);
            }
        }
        $addUser = $virtualConfig[$contestInfo['contest_level']][$count];
        // 添加 当前 虚拟完成用户
        $virtualComplete = 0;
        if ($count >= 5) {
            // 虚拟用户完成比例
            $virtualUser = 0;
            for ($i=0;$i<=$count;$i++) {
                $virtualUser += rand($addUser['min'], $addUser['max']);
            }
            $sql = 'SELECT rate FROM t_walk_contest_config WHERE contest_date <= ? AND contest_level = ? ORDER BY contest_date DESC';
            $rate = $db->getOne($sql, $date, $contestInfo['contest_level']);
            $virtualComplete = round($virtualUser * $rate /100);
        }

        $sql = 'UPDATE t_walk_contest SET virtual_complete_count = ?, complete_count = ? WHERE contest_id = ?';
        $db->exec($sql, $virtualComplete, $completeUserCount + $virtualComplete, $contestInfo['contest_id']);

        $sql = 'SELECT * FROM t_walk_contest WHERE contest_level = ? AND contest_date = ?';
        $tomorrowInfo = $db->getRow($sql, $contestInfo['contest_level'], date('Y-m-d', strtotime('+1 day')));
        if ($tomorrowInfo) {
            // 添加 明天 虚拟用户
            $virtualUser = $tomorrowInfo['virtual_count'] + rand($addUser['min'], $addUser['max']);
            $sql = 'SELECT COUNT(id) FROM t_walk_contest_user WHERE contest_id = ?';
            $totalUser = $db->getOne($sql, $tomorrowInfo['contest_id']);

            $sql = 'UPDATE t_walk_contest SET virtual_count = ?, total_count = ? WHERE contest_id = ?';
            $db->exec($sql, $virtualUser, $virtualUser + $totalUser, $tomorrowInfo['contest_id']);
        }

    }

}