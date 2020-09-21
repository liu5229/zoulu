<?php


class GoldReceiveModel extends AbstractModel
{
    public function insert ($params) {
//        if (REDIS_ENABLE) {
//            $redis = new \Redis();
//            $redis->pconnect(REDIS_NAME, 6379);
//            $redis->select(1);
//            $key = 're:' . $params['user_id'] . ':' . $params['type'];//提现key
//            if ($redis->setnx($key, '1')) {
//                $sql = 'INSERT INTO t_gold2receive SET user_id = ?, receive_gold = ?, receive_walk = ?, receive_type = ?, receive_status = ?, end_time = ?, is_double = ?, receive_date = ?';
//                $this->db->exec($sql, $params['user_id'], $params['gold'], $params['walk'] ?? 0, $params['type'], $params['status'] ?? 0, $params['end_time'] ?? '0000-00-00 00:00:00', $params['is_double'] ?? 0, $params['date'] ?? date('Y-m-d'));
//                $return = $this->db->lastInsertId();
//                if ($return) {
//                    $redis->del($key);
//                }
//                return $return;
//            } else {
//                if (-1 == $redis->ttl($key)) {
//                    $redis->expire($key, 2);
//                }
//                return FALSE;
//            }
//        }
        $sql = 'INSERT INTO t_gold2receive SET user_id = ?, receive_gold = ?, receive_walk = ?, receive_type = ?, receive_status = ?, end_time = ?, is_double = ?, receive_date = ?';
        $this->db->exec($sql, $params['user_id'], $params['gold'], $params['walk'] ?? 0, $params['type'], $params['status'] ?? 0, $params['end_time'] ?? '0000-00-00 00:00:00', $params['is_double'] ?? 0, $params['date'] ?? date('Y-m-d'));
        return $this->db->lastInsertId();
    }

    //批量插入待领取金币
    //参数顺序 user_id, receive_gold, receive_walk, receive_type, receive_date
    public function batchInsert ($data) {
        $sql = "INSERT INTO t_gold2receive (user_id, receive_gold, receive_walk, receive_type, receive_date) VALUES";
        foreach ($data as $line) {
            $sql .= '(' . implode(', ', $line) . '),';
        }
        $sql = rtrim($sql,',');
        $this->db->exec($sql);
    }
}