<?php 

Class AdminActivityController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM t_activity";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_activity ORDER BY activity_id LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
    
    public function detailAction () {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'edit':
                    if (isset($_POST['id'])) {
                        $sql = "UPDATE t_activity SET
                                activity_award_min = :activity_award_min,
                                activity_award_max = :activity_award_max,
                                activity_name = :activity_name,
                                activity_type = :activity_type,
                                activity_max = :activity_max,
                                activity_duration = :activity_duration,
                                activity_desc = :activity_desc
                                WHERE activity_id = :activity_id";
                        $return = $this->db->exec($sql, array('activity_award_min' => $_POST['activity_award_min'] ?? 0, 
                            'activity_award_max' => $_POST['activity_award_max'] ?? 0, 
                            'activity_name' => $_POST['activity_name'] ?? '', 
                            'activity_max' => $_POST['activity_max'] ?? '', 
                            'activity_duration' => $_POST['activity_duration'] ?? '', 
                            'activity_type' => $_POST['activity_type'] ?? '', 
                            'activity_desc' => $_POST['activity_desc'] ?? '', 
                            'activity_id' => $_POST['id']));
                        if ($return) {
                            return array();
                        } else {
                            throw new \Exception("Operation failure");
                        }
                    }
                    break;
                case 'add':
                    $sql = "INSERT INTO t_activity SET
                            activity_award_min = :activity_award_min,
                            activity_award_max = :activity_award_max,
                            activity_name = :activity_name,
                            activity_type = :activity_type,
                            activity_max = :activity_max,
                            activity_duration = :activity_duration,
                            activity_desc = :activity_desc";
                    $return = $this->db->exec($sql, array('activity_award_min' => $_POST['activity_award_min'] ?? 0, 
                        'activity_award_max' => $_POST['activity_award_max'] ?? 0, 
                        'activity_type' => $_POST['activity_type'] ?? '', 
                        'activity_name' => $_POST['activity_name'] ?? '', 
                        'activity_max' => $_POST['activity_max'] ?? '', 
                        'activity_duration' => $_POST['activity_duration'] ?? '', 
                        'activity_desc' => $_POST['activity_desc'] ?? ''));
                    if ($return) {
                        return array();
                    } else {
                        throw new \Exception("Operation failure");
                    }
                    break;
            }
        }
        $activityInfo = array();
        if (isset($_POST['activity_id'])) {
            $sql = "SELECT * FROM t_activity WHERE activity_id = ?";
            $activityInfo = $this->db->getRow($sql, $_POST['activity_id']);
        }
        if ($activityInfo) {
            return $activityInfo;
        }
        throw new \Exception("Error Activity Id");
    }
    
    public function configAction () {
        $type = $_POST['type'] ?? '';
        $sql = "SELECT COUNT(*) FROM t_award_config WHERE config_type = ?";
        $totalCount = $this->db->getOne($sql, $type);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_award_config WHERE config_type = ? ORDER BY counter_min LIMIT " . $this->page;
            $list = $this->db->getAll($sql, $type);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
    
    public function configDetailAction () {
        if (isset($_POST['action'])) {
            $dataArr = [];
            if ($_POST['id']) {
                $sql = 'UPDATE t_award_config SET ';
            } elseif ('walk' == $_POST['type']) {
                $sql = 'INSERT INTO t_award_config SET config_type = :config_type,';
                $dataArr['config_type'] = 'walk';
            } else {
                throw new \Exception("Operation failure");
            }
            if ('walk' == $_POST['type']) {
                $sql .= 'counter_min = :counter_min,';
                $dataArr['counter_min'] = $_POST['counter_min'];
            }
            $sql .= 'award_min = :award_min, award_max = :award_max ';
            $dataArr['award_min'] = $_POST['award_min'];
            $dataArr['award_max'] = $_POST['award_max'];
            if ($_POST['id']) {
                $sql .= 'WHERE config_id = :config_id';
                $dataArr['config_id'] = $_POST['id'];
            }
            $return = $this->db->exec($sql, $dataArr);
            if ($return) {
                return array();
            } else {
                throw new \Exception("Operation failure");
            }
        }
        $configInfo = array();
        if (isset($_POST['id'])) {
            $sql = "SELECT * FROM t_award_config WHERE config_id = ?";
            $configInfo = $this->db->getRow($sql, $_POST['id']);
        }
        if ($configInfo) {
            return $configInfo;
        }
        throw new \Exception("Error Config Id");
    }
}