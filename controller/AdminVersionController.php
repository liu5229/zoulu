<?php 

Class AdminVersionController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM t_version";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_version ORDER BY version_id DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
    
    public function detailAction () {
        if (isset($_POST['action']) && isset($_POST['id'])) {
            switch ($_POST['action']) {
                case 'edit' :
                    $uploadApk = '';
                    if (isset($_POST['version_url']['file']['response']['data'][0]['file']['name'])) {
                        $apkName = (ENV_PRODUCTION ? '' : 'test-') . 'release-' . ($_POST['version_name'] ?? '') . '-' . date('Ymd') . '-' . time() . '.apk';
                        $a = @rename(APP_DIR . $_POST['version_url']['file']['response']['data'][0]['file']['name'], APP_DIR . $apkName);
                        if (!$a) {
                            throw new \Exception("Upload failure");
                        }
                        $uploadApk = 'app/' . $apkName;
                        $oss = new Oss();
                        $uploadReturn = $oss->upload($uploadApk, APP_DIR . $apkName);
                        if ($uploadReturn !== TRUE) {
                            throw new \Exception("Upload Oss failure");
                        }
                    }
                    $dataArr = array();
                    //add `id` in t_version
                    if ($_POST['id']) {
                        $sql = 'UPDATE t_version SET ';
                    } else {
                        $sql = 'INSERT INTO t_version SET ';
                    }
                    $sql .= 'version_id = :version_id, version_name = :version_name, is_force_update = :is_force_update, version_log = :version_log, need_update_id = :need_update_id';
                    $dataArr['version_name'] = $_POST['version_name'] ?? '';
                    $dataArr['is_force_update'] = $_POST['is_force_update'] ?? 0;
                    $dataArr['version_log'] = $_POST['version_log'] ?? '';
                    $dataArr['version_id'] = $_POST['version_id'] ?? '';
                    $dataArr['need_update_id'] = $_POST['need_update_id'] ?? 0;
                    if ($uploadApk) {
                        $sql .= ', version_url = :version_url';
                        $dataArr['version_url'] = $uploadApk;
                    }
                    if ($_POST['id']) {
                        $sql .= ' WHERE id = :id';
                        $dataArr['id'] = $_POST['id'];
                    }
                    $return = $this->db->exec($sql, $dataArr);
                    break;
                case 'delete':
                    $sql = 'DELETE FROM t_version WHERE id = ?';
                    $return = $this->db->exec($sql, $_POST['id']);
                    break;
            }
            
            if ($return) {
                return array();
            } else {
                throw new \Exception("Operation failure");
            }
        }
        $versionInfo = array();
        if (isset($_POST['id'])) {
            $sql = "SELECT * FROM t_version WHERE id = ?";
            $versionInfo = $this->db->getRow($sql, $_POST['id']);
        }
        if ($versionInfo) {
            return $versionInfo;
        }
        throw new \Exception("Error Activity Id");
    }
    
    public function adListAction () {
        $sql = "SELECT COUNT(*) FROM t_version_ad";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_version_ad ORDER BY version_id DESC, app_name DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
    
    public function adDetailAction () {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'change':
                    $sql = 'UPDATE t_version_ad SET ad_status = NOT(ad_status) WHERE version_id = ? AND app_name = ?';
                    $return = $this->db->exec($sql, $_POST['version_id'], $_POST['app_name']);
                    break;
                case 'add' :
                    $sql = 'INSERT INTO t_version_ad SET ad_status = ?, version_id = ?, app_name = ?';
                    $return = $this->db->exec($sql, $_POST['ad_status'], $_POST['version_id'], $_POST['app_name']);
                    break;
            }
            if ($return) {
                return array();
            } else {
                throw new \Exception("Operation failure");
            }
        }
        throw new \Exception("错误操作");
    }
}