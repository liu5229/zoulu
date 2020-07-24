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
        $aop->appId = '2021001171644282';
        $aop->rsaPrivateKey = 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCisMCHp4xJeQtUPG4qhN+yElWPmEx34ctohoibhitNE+7H+B6AeZpO3DIzw9YW1dKuItpsNMWmhn/5Lu21DrDOZh4VaBG6SBBW2Iyq3uxlS2x+MiE2j0NqyfPO/fLpDlwUr1FuPzOvHbE1ssvVM9iL+ZnJcVf/tLH9wXwzATERMqinOH5N/39603de2tr7wEANKkwemBs9ptHu0F7MFhLOTP8471rX4i1U2U3W6g/bZmYwy3Vs3Wzd3946Ra1ticpWDVDV2sflrGwpkgi0KqDpiD138UwrDQj7fF7WvjwJBW7gLBMXqLcSFcOPVwmLiMoT7I95gxZig12H3QUFWf1BAgMBAAECggEACciBO2ca64wo3z7nDQ2Cei3aEVGCP69HURjN/DQ8RF1PfZzxEJ6/ZcCeEDjVlffzvF8CLYGa5SGvbmehCcNBZJgFdRoV/tK4kNBi3R+crZa0hn4zOxmwXyqXy7m/sr4XUXMdfXi1ffFWJ7mBwmdkvT4cPl3fgdP25CCPfG206qi9fJYw9OzlqpzYZEu5e3/cS5VGLXKBh+SjK6n9B63Zh82bp/OihWGesj4Vi7JbSBFOM7xrxy8uODDDW8q/lBWFojaNDsccQIonoXpLmARAfABIU5L8Xvh7givU4MFVc4TwmG8YLbkVipMQZ1wMAouPXJBfvxmCApazmPzKDTbspQKBgQDmLvwRbwqf7teq89kT2BZl2tW3S8mvnqf2Bwd44Hpo3Dyv9T9z6BV9pLa7EiAICxN9Bysa+rG4WZ7rj5Y59CrCe2XQinbEJZScX/q8eJfK6dpVw0zRCxViUe4Wi63Eh4wfWeKpLq5dWFVhIYq97gJ/RBRGY4NMYtY5AfBBhGXXCwKBgQC07+jrNPh48d6hy9oaVgx43Enxmg2w5818l524iUTuXnhHG7gOXYEzLBblBRqlHWQbcAAiFoR6B7rnU/WU8DzyLOEZ9s3k+6IYscGUtKpiGNVQO4pOb77Ys9yvBNSxIhcFg1GfpG4WtgJyxVUSvQ6lOb5peCJnlLWCmkvswHv8YwKBgHvfyy2CqaAaRBwu8KK6Rot38k2bTqXhZxiC/eVyQM4Pv+UdwZEZ0/7y1pfkEDLj6w/8/JifU2cXa+vvMPRtT1msWMWazoGOi+R/zosBBwdfRG2lFcDmCxMHbm7ZqqE6JRF2KQHNKm73q7MC/wxpexSMSbD7utwv0IOLZIWNv9SzAoGAblhhlBAZ+KiJPeM0gBs6P/sYnV92Og0kJHfSmFge0cCLWdJtzVT5FlwtGj6ioU/rXVBQxHk3EbTlJ27stohMouT74vnBV4SetrCxfh8wSeMbNHMbRfqgSUhnrdUkYWKI57POc62z9eXKWHRADc1+wQUWOvwo/0KR77Rp2VkKREECgYAeZej/HA/UgVeX3D4MMqD7Qsopd/UNvqMysZTSqku7ZVmRmsaNU/fRwb6IYSNUA9tJa/F+AamsbfyAI6v9GD+uqQXvQHDrRDaoyZSC4X1DktCc96/W4GsRXLUQLjW7ltsp5FJtaHeELm2TgVrldduQe85tcUIf/q/KqGFtJBbTrw==';
//        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuxb35EVtg101fpcW1VxIoHXEOKQZtMx6PowJ9kqDVGS1cqi6sQpnSKkqYJ/5Kt7XZUma5Ldj9SyOh7uKhmXV0zGKpb7hKHZO4KT0oxOSysdUhdeYg2hUrqu3I9z7uhF/RVIsp5HfjcwDbUVZsYHe1TD4YMzP6UAuCBgD1zi61Dg4elakrmVStVBXqTacbFb69+jc+XRcl2eBrO+m5U2qHVlqH8wmUx7lGkN5ARXP7AudmvcqR0ChXzzVyWuK3qdjc5XEIWDR1bJZvZO5dHNUEh2OJ8jvp0CrOBkfhMHt6MvfwVi4pD510l1Y6311dT9sqgSVSCYLjVvFoFpvgv/OWQIDAQAB';
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
        var_dump($result);
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




