<?php 

Class AdminIndexController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM t_report";
        $totalCount = $this->db->getOne($sql);
        $sql = 'SELECT "合计" report_date, 
            SUM(withdraw_value) withdraw_value, 
            SUM(withdraw_count) withdraw_count, 
            SUM(new_user) new_user, 
            SUM(new_gold) new_gold, 
            SUM(login_user) login_user, 
            SUM(share_count) share_count
            FROM t_report';
        $total = $this->db->getRow($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_report ORDER BY report_date DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => array_merge(array($total), $list)
        );
    }
}