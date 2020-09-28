<?php


class WithdrawModel extends AbstractModel
{

    public function updateStatus ($params) {
        if (!isset($params['withdraw_status']) || !isset($params['withdraw_id'])) {
            return FALSE;
        }
        $sql = 'UPDATE t_withdraw SET withdraw_status = :withdraw_status, withdraw_remark = :withdraw_remark, change_time = :change_time WHERE withdraw_id = :withdraw_id';
        return $this->db->exec($sql, array('withdraw_status' => $params['withdraw_status'],'withdraw_remark' => $params['withdraw_remark'] ?? '','change_time' => date('Y-m-d H:i:s'),'withdraw_id' => $params['withdraw_id']));
    }
}