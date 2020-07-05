<?php 

Class ApiController extends AbstractController {

    /**
     * 推啊的农场活动
     * @return false|string
     */
    public function tuiaFarmAction () {
        //tuia_farm
        // userId 用户 id，用户在媒体下的唯一识别信息，来源 于活劢链接中的 &userId=xxx，由媒体拼接提 供
        // timestamp 时间戳，系统当前毫秒数
        // prizeFlag 请求充值的虚拟商品在对接方媒体系统内的标识 符，用于标识具体的虚拟商品，具体由媒体提供
        // orderId  推啊订单号，具有唯一性，幂等由媒体保障
        // appKey 媒体信息
        // sign 签名
        // score 如果充值的是数值类型的虚拟商品，则同时请求 充值对应的数值score，比如积分、金币等
        // reason 充值理由
        if (DEBUG_MODE) {
            //add api log
            $logFile = LOG_DIR . 'access/' . date('Ymd') . '/';
            if (!is_dir($logFile)) {
                mkdir($logFile, 0755, true);
            }
            file_put_contents($logFile . 'access_' . date('H') . '.log', date('Y-m-d H:i:s') . '|tuia-farm|' . json_encode($_POST) . '|' . PHP_EOL, FILE_APPEND);
        }
        if (isset($_POST['userId']) && isset($_POST['timestamp']) && isset($_POST['prizeFlag']) && isset($_POST['orderId']) && isset($_POST['appKey']) && isset($_POST['sign']) && isset($_POST['reason'])) {
            $goldArr = array('tuia_farm1' => 500, 'tuia_farm2' => 100);
            if (!in_array($_POST['prizeFlag'], array('tuia_farm1', 'tuia_farm2'))) {
                $return = array('code' => '607', 'msg' => '无效奖励', 'orderId' => $_POST['orderId'], 'extParam' => array('deviceId' => '', 'userId' => $_POST['userId']));
                return json_encode($return);
            }
            $changeGold = $goldArr[$_POST['prizeFlag']];
            //时效性验证
            if (!$_POST['timestamp'] || abs($_POST['timestamp'] - time() * 1000) > 1000 * 60 * 5) {
                $return = array('code' => '602', 'msg' => '验证时效性失败', 'orderId' => $_POST['orderId'], 'extParam' => array('deviceId' => '', 'userId' => $_POST['userId']));
                return json_encode($return);
            }
            if ('2i6pkgFrvhovviEgjBxZT3e5beS9' != $_POST['appKey']) {
                $return = array('code' => '603', 'msg' => '验证appKey失败', 'orderId' => $_POST['orderId'], 'extParam' => array('deviceId' => '', 'userId' => $_POST['userId']));
                return json_encode($return);
            }
            //签名验证
            if (md5($_POST['timestamp'] . $_POST['prizeFlag'] . $_POST['orderId'] . $_POST['appKey'] . '3WkzTvoaCAKQ9cRNHzRgCtHtf6PWsFtNQRrmQpt') != $_POST['sign']) {
                $return = array('code' => '604', 'msg' => '验证签名失败', 'orderId' => $_POST['orderId'], 'extParam' => array('deviceId' => '', 'userId' => $_POST['userId']));
                return json_encode($return);
            }
            $sql = 'SELECT user_id, imei FROM t_user WHERE device_id = ?';
            $userInfo = $this->db->getRow($sql, $_POST['userId']);
            if (!$userInfo) {
                $return = array('code' => '605', 'msg' => '无效用户', 'orderId' => $_POST['orderId'], 'extParam' => array('deviceId' => '', 'userId' => $_POST['userId']));
                return json_encode($return);
            }
            //插入访问日志
            $sql = 'INSERT INTO t_api_log (`source`, `type`, `order_id`, `user_id`, `params`) SELECT :source, :type, :order_id, :user_id, :params FROM DUAL WHERE NOT EXISTS (SELECT log_id FROM t_api_log WHERE source = :source AND order_id = :order_id)';
            $result = $this->db->exec($sql, array('source' => 'tuia', 'type' => 'tuia_farm', 'order_id' => $_POST['orderId'], 'user_id' => $userInfo['user_id'], 'params' => json_encode($_POST)));
            if ($result) {
                //添加金币
                $sql = 'INSERT INTO t_gold SET user_id = :user_id, change_gold = :change_gold, gold_source = :gold_source, change_type = :change_type, relation_id = :relation_id, change_date = :change_date';
                $this->db->exec($sql, array('user_id' => $userInfo['user_id'], 'change_gold' => $_POST['score'], 'gold_source' => 'tuia_farm', 'change_type' => 'in', 'relation_id' => $this->db->lastInsertId(), 'change_date' => date('Y-m-d')));
                //返回数据
                $return = array('code' => '0', 'msg' => '', 'orderId' => $_POST['orderId'], 'extParam' => array('deviceId' => $userInfo['imei'], 'userId' => $_POST['userId']));
                return json_encode($return);
            } else {
                //返回数据
                $return = array('code' => '606', 'msg' => '不能重复添加', 'orderId' => $_POST['orderId'], 'extParam' => array('deviceId' => $userInfo['imei'], 'userId' => $_POST['userId']));
                return json_encode($return);
            }
        } else {
            //code “0”:成功，“-1”:重填，“其他”:充值异 常。注意:响应 code 类型需为 String
            //msg 充值失败信息
            //orderId 推啊订单号 String
            // extParam 用户设备id，Android:imei;ios:idfa 用户id:用户唯一标识
            //{ "deviceId":"867772035090410", "userId":"123456"
            //}
            $return = array('code' => '601', 'msg' => '缺少参数', 'orderId' => $_POST['orderId'] ?? '', 'extParam' => array('deviceId' => '', 'userId' => $_POST['userId'] ?? ''));
            return json_encode($return);
        }
    }

    /**
     * 鱼玩盒子 活动
     * @return false|string
     */
    public function yuwanBoxAction () {
        //yuwan
//        参数名	必选	类型	说明
        //orderNo	是	string	新量象平台唯一订单号
        //rewardDataJson	是	string	领取奖励信息（json_encode）
        //sign	是	string	签名
        //time	是	int	发送时 时间戳 (单位秒)

//        rewardDataJson参数名	必选	类型	说明
//advertName	是	string	广告名称
//rewardRule	是	string	用户领取奖励规则标题
//stageId	是	int	广告期数id
//stageNum	是	string	广告期数信息
//advertIcon	是	string	广告icon
//rewardType	是	string	1:试玩 2:充值 3.冲刺奖励 4:注册奖励 5:奖励卡奖励(全额给用户)
//isSubsidy	是	int	0 否 1 是 新量象平台补贴
//mediaMoney	是	float	媒体方可获取的金额，单位元
//rewardUserRate	是	float	领取时媒体设置的用户奖励比
//currencyRate	是	float	媒体设置的媒体币兑换比率
//userMoney	是	float	用户领取的金额, 单位元
//userCurrency	是	float	用户领取的媒体币，(userCurrency = userMoney * currencyRate)
//mediaUserId	是	int	媒体方登录用户ID
//receivedTime	是	int	奖励收取时间 (时间戳，单位秒)
        if (isset($_POST['orderNo']) && isset($_POST['rewardDataJson']) && isset($_POST['sign']) && isset($_POST['time'])) {
            //时效性验证
            if (!$_POST['time'] || abs($_POST['time'] - time()) > 1000 * 60 * 5) {
                $return = array('code' => '702', 'msg' => '验证时效性失败');
                return json_encode($return);
            }
            //签名验证
            if (md5($_POST['rewardDataJson'] . $_POST['time'] . '5sddovjriiay7q897nsuccc7gvntcj9z') != $_POST['sign']) {
                $return = array('code' => '704', 'msg' => '验证签名失败');
                return json_encode($return);
            }
            $sql = 'SELECT user_id, imei FROM t_user WHERE device_id = ?';
            $rewardData = json_decode($_POST['rewardDataJson'], true);
            $userInfo = $this->db->getRow($sql, $rewardData['mediaUserId'] ?? '');
            if (!$userInfo) {
                $return = array('code' => '705', 'msg' => '无效用户');
                return json_encode($return);
            }
            //插入访问日志
            $sql = 'INSERT INTO t_api_log (`source`, `type`, `order_id`, `user_id`, `params`) SELECT :source, :type, :order_id, :user_id, :params FROM DUAL WHERE NOT EXISTS (SELECT log_id FROM t_api_log WHERE source = :source AND order_id = :order_id)';
            $result = $this->db->exec($sql, array('source' => 'yuwan', 'type' => 'yuwan_box', 'order_id' => $_POST['orderNo'], 'user_id' => $userInfo['user_id'], 'params' => json_encode($_POST)));
            if ($result) {
                //添加金币
                $sql = 'INSERT INTO t_gold SET user_id = :user_id, change_gold = :change_gold, gold_source = :gold_source, change_type = :change_type, relation_id = :relation_id, change_date = :change_date';
                $this->db->exec($sql, array('user_id' => $userInfo['user_id'], 'change_gold' => $rewardData['userCurrency'] ?? 0, 'gold_source' => 'yuwan_box', 'change_type' => 'in', 'relation_id' => $this->db->lastInsertId(), 'change_date' => date('Y-m-d')));
                //返回数据
                $return = array('code' => '0', 'msg' => '');
                return json_encode($return);
            } else {
                //返回数据
                $return = array('code' => '706', 'msg' => '不能重复添加');
                return json_encode($return);
            }
        } else {
            //code “0”:成功，“-1”:重填，“其他”:充值异 常。注意:响应 code 类型需为 String
            //msg 充值失败信息
            //orderId 推啊订单号 String
            // extParam 用户设备id，Android:imei;ios:idfa 用户id:用户唯一标识
            //{ "deviceId":"867772035090410", "userId":"123456"
            //}
            $return = array('code' => '701', 'msg' => '缺少参数');
            return json_encode($return);
        }
    }

    /**
     * 热云回调接口
     * @return false|string
     */
    public function reyunAction () {
        //channel String 渠道名 广点通，今日头条
//        imei String Android 设备 ID 866280041545123
//        appkey String 产品的唯一标示 在热云 trackingio平台生成的appkey f819f9cac5c030f812b2067d0cf8 18f7
//        skey String 生成规则: MD5(format("%s_%s_%s", activeTime,大写 appkey, securitykey)).toUpperCase Securitykey 由广告主提供
        if (REYUN_DEBUG) {
            //add api log
            $logFile = LOG_DIR . 'access/' . date('Ymd') . '/';
            if (!is_dir($logFile)) {
                mkdir($logFile, 0755, true);
            }
            file_put_contents($logFile . 'access_' . date('H') . '.log', date('Y-m-d H:i:s') . '|reyun|' . json_encode($_GET) . '|' . PHP_EOL, FILE_APPEND);
        }
        if (isset($_GET['spreadname']) && isset($_GET['imei']) && isset($_GET['appkey']) && isset($_GET['skey']) && isset($_GET['activetime'])) {
            if ('bec5fa78bd65aff94ca5d775df4ad294' != $_GET['appkey']) {
                $return = array('code' => '802', 'msg' => '验证appkey失败');
                return json_encode($return);
            }
            if (!$_GET['spreadname']) {
                $return = array('code' => '803', 'msg' => '渠道号空');
                return json_encode($return);
            }
            //securitykey：reyun_jingyun
            if (strtoupper(md5($_GET['activetime'] . '_' . strtoupper($_GET['appkey']) . '_' . 'reyun_jingyun')) != $_GET['skey']) {
                $return = array('code' => '804', 'msg' => '验证签名失败');
                return json_encode($return);
            }
            $sql = 'SELECT user_id FROM t_user WHERE imei = ?';
            $userId = $this->db->getOne($sql, $_GET['imei']);
            if (!$userId) {
                $sql = 'INSERT INTO t_reyun_log SET imei = ?, app_name = ?, params = ?';
                $this->db->exec($sql, $_GET['imei'], $_GET['spreadname'], json_encode($_GET));
                $return = array('code' => '803', 'msg' => '无效用户');
                return json_encode($return);
            }
            $sql = 'UPDATE t_user SET reyun_app_name = ? WHERE user_id = ?';
            $this->db->exec($sql, $_GET['spreadname'], $userId);
            $return = array('code' => '200', 'msg' => '更新成功');
            return json_encode($return);
        } else {
            //code “0”:成功，“-1”:重填，“其他”:充值异 常。注意:响应 code 类型需为 String
            //msg 充值失败信息
            //orderId 推啊订单号 String
            // extParam 用户设备id，Android:imei;ios:idfa 用户id:用户唯一标识
            //{ "deviceId":"867772035090410", "userId":"123456"
            //}
            $return = array('code' => '801', 'msg' => '缺少参数');
            return json_encode($return);
        }
    }
}