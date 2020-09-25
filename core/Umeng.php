<?php

Class Umeng {
    protected $apiKey;
    protected $apiSecurity;
    protected $appkey;

    public function __construct () {
        $this->apiKey = UMENG_APIKEY;
        $this->apiSecurity = UMENG_APISECURITY;
        $this->appkey = UMENG_APPKEY;
    }
    
    public function verify ($zToken) {
        if (!$zToken) {
            return FALSE;
        }
        $url = 'param2/1/com.umeng.trustid/umeng.trustid.getAntiScoreByZtoken/' . $this->apiKey;
        $urlRequest = 'http://gateway.open.umeng.com/openapi/' . $url;
        
        $requestData = array(
            'ztoken' => $zToken,
            'appkey' => $this->appkey,
        );
        
        $params = array();
        foreach ($requestData as $k => $v) {
            $params[] = $k . $v;
        }
        sort($params);
        $requestData['_aop_signature'] = strtoupper(bin2hex(hash_hmac("sha1", $url . implode($params), $this->apiSecurity, true)));
        
        $ch = curl_init ();
        $paramToSign = "";
        foreach ( $requestData as $k => $v ) {
            $paramToSign = $paramToSign . $k . "=" . urlencode($v) . "&";
        }
        $paramLength = strlen ( $paramToSign );
        if ($paramLength > 0) {
            $paramToSign = substr ( $paramToSign, 0, $paramLength - 1 );
        }
        
        curl_setopt ( $ch, CURLOPT_URL, $urlRequest );
        curl_setopt ( $ch, CURLOPT_HEADER, false );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 120 );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $paramToSign );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
        $data = json_decode(curl_exec ( $ch ));
        if (isset($data->suc) && TRUE === $data->suc && is_numeric($data->score)) {
            return $data->score;
        }
        return FALSE;
    }
    
}
