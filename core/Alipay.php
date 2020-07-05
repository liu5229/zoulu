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
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAuxb35EVtg101fpcW1VxIoHXEOKQZtMx6PowJ9kqDVGS1cqi6sQpnSKkqYJ/5Kt7XZUma5Ldj9SyOh7uKhmXV0zGKpb7hKHZO4KT0oxOSysdUhdeYg2hUrqu3I9z7uhF/RVIsp5HfjcwDbUVZsYHe1TD4YMzP6UAuCBgD1zi61Dg4elakrmVStVBXqTacbFb69+jc+XRcl2eBrO+m5U2qHVlqH8wmUx7lGkN5ARXP7AudmvcqR0ChXzzVyWuK3qdjc5XEIWDR1bJZvZO5dHNUEh2OJ8jvp0CrOBkfhMHt6MvfwVi4pD510l1Y6311dT9sqgSVSCYLjVvFoFpvgv/OWQIDAQABAoIBAQCYK7la8OF/PHv7R/bpeZMU+FSuYUMLXFl9sDeWHMsvBG7VIMogn76cSgPO7a8joHb/ylty9nsV+rS/T9n/MKs3iQ8letj2KSxE6caVMaFuz6w+5LoHAAAIxmBCikYw9HRZNNpfPXXghnSvFv46M9DEBH2xdkURigMm0Cmnj1veqaspv9cGw4hL8Fss5+Wcs09MIZrVYu6ObsAsT8hDS4pzcMoPIg+8JEiub5rKFMYCZ4Vz6bZVAf+JI4ohB14VT8pfwnAizAd4K5vGyTHAOYXKLclFRROf2U4mY0CoT0kHN598VDcCdAACgm7Ts5BlRKIZ1Yc0UxuQe7Fdo9Wy1zLFAoGBAOeVwSRa+JPtCUXkGhVLZX4Mo8558Hnju0Mcpl83YZ3/JbZVZSyFfrHuToCUhuFRYsB8d4kyy7IYcIHs4qhY193ksGmd66PWiZaU+9Ozgk50ztAnKmySgNWlGmyLBv0gKAhInsA3iSQAMXDjeMrXaIoqgO3Qib4jPS5WbxG7lnkTAoGBAM7QVOTEsfCrQekyXn99YN5mKuIFj3DWHrv7YOkcqj+xUnmDFXLE14HFiBJMAjxu9WrHJOct5iviVBNBO3Kusz2x6YcJbc06qZdN+MRkpQ4bWQgq/seZcvDNJGxGQljMv3P2hCznWdpRD/keP1MJvPklCnFAfrbj3170Blmo8JRjAoGAMNll5EVMKefWDOgQmG1O+0evRd5y20MuPPnOHkeQT8OCMPPSY5HFJ8MczAKIcP651eBrVoVgcjC1irJtHRWgcy3KCH1HN0gJvbmvZPh4hBQfi8i3Ki+8/VPWPw8UalBeIWEwG1ubkfx4cVeKIz9MzdgpWgCjDXhhb2TMCPGIzAECgYEAkmBPha9VxeJgfx3AVnm2AxLKzThkQte53xDXkYZRVU0683f8yUNayKW3XkPf3UgneVDD5L/OxkalfQ9RdSUDBeqTP5lD1trrR4TPSql1TRu8ExTjSQBpotd/LQc5VEJuSzQybtm79dIj/Q0UgsBEuQ4naurcBLQZ18ndaL4ysMMCgYBy585I+G4xNvKwc+cSC+eeQVnbeC8HOHjaHC7JnyL3cIdU40+PFZCaQHEe/sarli+xT5ZlBa2pI6i4c6bXoTT5YFtVX8Rhj06pdzP2Hwi82S19OAxkdQ0g5jr7PGZrDGLK0A+mNbuTJRL/NOQyyEjUrDdKFgS5kgtdvpj56TITkQ==';
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




