<?php 

Class AdminSdkController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM t_sdk_error";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT s.*, u.brand, u.model FROM t_sdk_error s
                LEFT JOIN t_user u USING(user_id)
                ORDER BY s.sdk_id DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
}