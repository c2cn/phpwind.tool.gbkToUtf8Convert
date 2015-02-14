<?php
//error_reporting(E_ALL | E_ERROR | E_PARSE);
error_reporting(E_ERROR | E_PARSE);
define('WIND_DEBUG', 0);
define('SLASH', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__) . SLASH);
define('PW_ROOT', ROOT . '..' . SLASH);

require_once PW_ROOT . 'wind' . SLASH . 'Wind.php';
Wind::application('convert', 'conf/config.php')->run();
