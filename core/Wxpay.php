<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Class Wxpay {
    protected $retryCount = 0;
    
    
    public function __construct () {
        
    }
    
    public function transfer ($amount, $openId, $orderNumber = '') {
        if (!PAY_MODE) {
            return TRUE;
        }
//        $openId = 'oS0AYxI1hoEyqqHgRFZINK7UiqEA';
//        $amount = 100;
        $createList = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
//        $createList = '0123456789';
        $nonceStr = '';
        for($i=0;$i<32;$i++) {
            $nonceStr .= $createList{rand(0, 33)};
        }
        $partnerTradeNo = $orderNumber ?: ('JIBUBAO' . time() . substr($nonceStr, 1, 5));
        
        $data = array (
            'amount' => $amount * 100 , //单位是分
//            'amount' => $amount , //单位是分
            'check_name' => 'NO_CHECK',//NO_CHECK：不校验真实姓名 FORCE_CHECK：强校验真实姓名
            'desc' => '计步宝提现',//企业付款备注，必填。注意：备注中的敏感词会被转成字符*
            'mch_appid' => 'wx3557b6d57ab8062d',//申请商户号的appid或商户号绑定的appid
            'mchid' => '1578766581',//微信支付分配的商户号
            'nonce_str' => $nonceStr,//随机字符串，不长于32位
            'openid' => $openId,//商户appid下，某用户的openid
            'partner_trade_no' => $partnerTradeNo,//商户订单号，需保持唯一性(只能是字母或者数字，不能包含有其它字符)
//            're_user_name' => ''//如果check_name设置为FORCE_CHECK，则必填用户真实姓名
            'spbill_create_ip' => '10.10.10.10'//该IP同在商户平台设置的IP白名单中的IP没有关联，该IP可传用户端或者服务端的IP。
        );
        $strArr = array();
        foreach ($data as $key => $value) {
            $strArr[] = $key . '=' . $value;
        }
        $strArr[] = 'key=23a365d18f89691ad645049f67d8064e';
        $data['sign'] = strtoupper(md5(implode('&', $strArr)));
//        $data['sign'] = '1EDB89804A9897531CFF314CEB14052A';
        $xml = '<xml>';
//        var_dump($data);
        foreach ($data as $key => $value) {
            $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
        }
        $xml .= '</xml>';
        
        $curl = curl_init();//初始一个curl会话
        curl_setopt($curl, CURLOPT_URL,"https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers");//设置url
        curl_setopt($curl, CURLOPT_POST, true);//设置发送方式：post
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml); //设置发送数据
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);//TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,true); 
        curl_setopt($curl, CURLOPT_SSLCERT, CERT_DIR . 'apiclient_cert.pem'); //client.
        curl_setopt($curl, CURLOPT_SSLKEY, CERT_DIR . 'apiclient_key.pem');
        
        $return_xml = curl_exec($curl);//执行cURL会话 ( 返回的数据为xml )
        curl_close($curl);//关闭cURL资源，并且释放系统资源
//        var_dump($return_xml);
        
        if ($return_xml) {
            libxml_disable_entity_loader(true);//禁止引用外部xml实体
            $value_array = json_decode(json_encode(simplexml_load_string($return_xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);//先把xml转换为simplexml对象，再把simplexml对象转换成 json，再将 json 转换成数组。
//            var_dump($value_array);
            if (isset($value_array['err_code'])) {
                if ('SYSTEMERROR' === $value_array['err_code'] && $this->retryCount < 5) {
                    $this->retryCount++;
                    return $this->transfer($amount, $openId);
                }
                return $value_array['err_code'] . ':' . ($value_array['err_code_des'] ?? '');
            } else {
                return TRUE;
            }
        } else {
            return '请求微信支付接口失败';
        }
        
    }
}