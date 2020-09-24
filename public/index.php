<?php
try {
    require_once 'init.inc.php';
    $requestUrl = strtok($_SERVER['REQUEST_URI'], '?');
    // remove the base path
    $requestUrl = substr($requestUrl, strlen('/'));
    // raw urldecode
    $requestUrl = rawurldecode($requestUrl);
    
    $routerArr = explode('/', $requestUrl);
    if (isset($routerArr[0]) && $routerArr[0]) {
        $controllerName = preg_replace('/\s+/', '', ucwords(str_replace('-', ' ', $routerArr[0])));
        $fullControllerName = $controllerName . 'Controller';
        $controller = new $fullControllerName();
        $initRes = $controller->init();
        if ($initRes instanceof apiReturn) {
            $result = $initRes;
        } else {
            if (isset($routerArr[1]) && $routerArr[1]) {
                $actionName = preg_replace('/\s+/', '', ucwords(str_replace('-', ' ', $routerArr[1])));
                $fullActionName = $actionName . 'Action';
                if (method_exists($controller, $fullActionName)) {
                    $result = $controller->$fullActionName();
                } else {
                    throw new \Exception("Can't autoload action " . $actionName);
                }
            } else {
                throw new \Exception("Need a action");
            }
        }
    } else {
        throw new \Exception("Need a controller");
    }
    if ($result instanceof apiReturn) {
        $return = array('code' => $result->code, 'data' => $result->data, 'msg' => $result->msg);
    } elseif (is_array($result)) {
        $return = array('status' => 'ok', 'data' => $result, 'msg' => '');
    } else {
        echo $result;
        exit;
    }
} catch(\Exception $e) {
    $return = array('status' => 'error', 'data' => '', 'msg' => $e->getMessage());
}

if (DEBUG_MODE) {
    //add api log
    $logFile = LOG_DIR . 'access/' . date('Ymd') . '/';
    if (!is_dir($logFile)) {
        @mkdir($logFile, 0755, true);
    }
    @file_put_contents($logFile . 'access_' . date('H') . '.log', date('Y-m-d H:i:s') . '|' . ($_SERVER['REQUEST_URI'] ?? '') . '|' . json_encode(json_decode(file_get_contents("php://input"), TRUE)) . '|' . json_encode($return, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
}

//var_dump($return);
echo json_encode($return);
