<?php

define('ROOT_DIR', dirname(__DIR__)  . '/');
define('PUBLIC_DIR', ROOT_DIR . 'public/');
define('CONFIG_DIR', ROOT_DIR  . 'config/');
define('CORE_DIR', ROOT_DIR  . 'core/');
define('CERT_DIR', ROOT_DIR  . 'cert/');
define('CONTROLLER_DIR', ROOT_DIR  . 'controller/');
define('MODEL_DIR', ROOT_DIR  . 'model/');
define('LOG_DIR', ROOT_DIR . 'log/');
define('APP_DIR', PUBLIC_DIR  . 'app/');
define('IMG_DIR', PUBLIC_DIR  . 'img/');
define('UPLOAD_DIR', PUBLIC_DIR  . 'upload/');
define('UPLOAD_IMAGE_DIR', UPLOAD_DIR  . 'image/');

/**
 * load the private configure
 */
if (file_exists(CONFIG_DIR . 'config.private.php')) {
    include CONFIG_DIR . "config.private.php";
}

!defined('HOST_NAME') && define('HOST_NAME', '127.0.0.1/');
!defined('HOST_OSS') && define('HOST_OSS', 'https://oss.stepcounter.cn/');

!defined('DB_HOST') && define('DB_HOST', '192.168.0.25');
!defined('DB_PORT') && define('DB_PORT', 3306);
!defined('DB_USERNAME') && define('DB_USERNAME', 'root');
!defined('DB_PASSWORD') && define('DB_PASSWORD', '123456');
!defined('DB_DATABASE') && define('DB_DATABASE', 'jy_walk');

!defined('DEBUG_MODE') && define('DEBUG_MODE', FALSE);
!defined('REYUN_DEBUG') && define('REYUN_DEBUG', FALSE);
!defined('ENV_PRODUCTION') && define('ENV_PRODUCTION', FALSE);

!defined('JY_WALK_ADMIN_USER') && define('JY_WALK_ADMIN_USER', 'username');
!defined('JY_WALK_ADMIN_PASSWORD') && define('JY_WALK_ADMIN_PASSWORD', '123456');

!defined('PAY_MODE') && define('PAY_MODE', FALSE);
/**
 * register autoload
 */
require CORE_DIR . 'AutoLoad.php';
AutoLoad::register();
