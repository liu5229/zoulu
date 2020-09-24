<?php 

Class AdminAdController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM t_advertise";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_advertise ORDER BY advertise_id LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
    
    public function detailAction () {
        if (isset($_POST['action']) && isset($_POST['id'])) {
            $uploadImg = '';
            if (isset($_POST['advertise_image']['file']['response']['data'][0]['file']['name'])) {
                $uploadImg = 'img/' . (ENV_PRODUCTION ? '' : 'test-') . time() . $_POST['advertise_image']['file']['response']['data'][0]['file']['name'];

                $oss = new Oss();
                $uploadReturn = $oss->upload($uploadImg, IMG_DIR . $_POST['advertise_image']['file']['response']['data'][0]['file']['name']);
                if ($uploadReturn !== TRUE) {
                    throw new \Exception("Upload Oss failure");
                }
            }
            switch ($_POST['action']) {
                case 'edit':
                    if ($_POST['id']) {
                        $sql = 'UPDATE t_advertise SET ';
                    } else {
                        $sql = 'INSERT INTO t_advertise SET ';
                    }
                    $sql .= 'advertise_name = :advertise_name,
                            advertise_subtitle = :advertise_subtitle,
                            advertise_type = :advertise_type,
                            advertise_url = :advertise_url,
                            advertise_location = :advertise_location,
                            advertise_status = :advertise_status,
                            advertise_validity_type = :advertise_validity_type,
                            advertise_validity_start = :advertise_validity_start,
                            advertise_validity_end = :advertise_validity_end,
                            advertise_validity_length = :advertise_validity_length';
                    $dataArr = array('advertise_name' => $_POST['advertise_name'] ?? 0, 
                            'advertise_subtitle' => $_POST['advertise_subtitle'] ?? '', 
                            'advertise_type' => $_POST['advertise_type'] ?? 0, 
                            'advertise_url' => $_POST['advertise_url'] ?? '', 
                            'advertise_location' => $_POST['advertise_location'] ?? '', 
                            'advertise_status' => $_POST['advertise_status'] ?? '', 
                            'advertise_validity_type' => $_POST['advertise_validity_type'] ?? 'fixed', 
                            'advertise_validity_start' => $_POST['advertise_validity_start'] ?? 0, 
                            'advertise_validity_end' => $_POST['advertise_validity_end'] ?? 0, 
                            'advertise_validity_length' => $_POST['advertise_validity_length'] ?? 0);
                    if ($uploadImg) {
                        $sql .= ', advertise_image = :advertise_image';
                        $dataArr['advertise_image'] = $uploadImg;
                    }
                    if ($_POST['id']) {
                        $sql .= ' WHERE advertise_id = :advertise_id';
                        $dataArr['advertise_id'] = $_POST['id'];
                    }
                    $return = $this->db->exec($sql, $dataArr);
                    if ($return) {
                        return array();
                    } else {
                        throw new \Exception("Operation failure");
                    }
                    break;
            }
        }
        $activityInfo = array();
        if (isset($_POST['id'])) {
            $sql = "SELECT * FROM t_advertise WHERE advertise_id = ?";
            $activityInfo = $this->db->getRow($sql, $_POST['id']);
        }
        if ($activityInfo) {
            return $activityInfo;
        }
        throw new \Exception("Error Id");
    }
}