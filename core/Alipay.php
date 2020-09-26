<?php
//https://docs.open.alipay.com/api_28/alipay.fund.trans.uni.transfer/
require_once 'alipay/AopClient.php';
require_once 'alipay/request/AlipayFundTransUniTransferRequest.php';
require_once 'alipay/request/AlipaySystemOauthTokenRequest.php';
require_once 'alipay/request/AlipayUserInfoShareRequest.php';

class Alipay {
    protected $aop;

    public function __construct () {
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = ALI_APPID;
        $aop->rsaPrivateKey = ALI_PRIVATEKEY;
//        $aop->alipayrsaPublicKey = ALI_PUBLICKEY;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $this->aop = $aop;
    }

    public function token ($code) {
        $request = new \AlipaySystemOauthTokenRequest ();
        $request->setGrantType("authorization_code");
//        $code = "255ad1ecf4fd47c9971c14db1479YA53";
        $request->setCode($code);
        $result = $this->aop->execute ( $request);
        if (isset($result->error_response)) {
            return FALSE;
        }
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        if (isset($result->$responseNode->code) && $result->$responseNode->code != 10000){
            return FALSE;
        } else {
            return $result->$responseNode->access_token;
        }
    }

    public function info ($token) {
//        $accessToken = 'kuaijieB44d5dccae72a41719abd742da87ffX53';
        $request = new \AlipayUserInfoShareRequest ();
        $result = $this->aop->execute ( $request , $token );
//        var_dump($result);
        if (isset($result->error_response)) {
            return FALSE;
        }
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        if (isset($result->$responseNode->code) && $result->$responseNode->code != 10000){
            return FALSE;
        } else {
            return $result->$responseNode->user_id;
        }
    }
    
}




