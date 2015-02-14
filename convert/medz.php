<?php
error_reporting(E_ERROR | E_PARSE);

define('SLASH', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__) . SLASH);
define('PW_ROOT', ROOT . '..' . SLASH);

$step = $_GET['step'];
$medz = $_GET['medz'];
$back = $_GET['back'];
$step or $step = 1;
$step = intval($step);
if($medz != 'medz' or !$back) {
	show('非法操作!');
}
if($step == 1) {
	$files = include ROOT . 'conf' . SLASH . 'files.php';
	$dirs  = include ROOT . 'conf' . SLASH . 'dirs.php';
	foreach($files as $file) {
		@unlink($file);
	}
	foreach($dirs as $dir) {
		clearRecur($dir);
	}
	jump('环境清理完成~', 2);
}
if($step != 2) {
	exit;
}
include ROOT . 'service' . SLASH . 'zip.php';
$zip = new Zip();
$zip->init();
$datas = $zip->extract(ROOT. 'data' . SLASH . 'phpwind.zip');
foreach($datas as $data) {
	$data['filename'] = preg_replace("/(\/)/is", SLASH, str_replace("\\", SLASH, $data['filename']));
	$dir = dirname($data['filename']);
	if($dir != '.') {
		createFolder(PW_ROOT . $dir);
	}
	file_put_contents(PW_ROOT . $data['filename'], $data['data']);
}
show('站点转换成功~下一步将进行初始化站点配置~', urldecode($back));
// #url组装方法
function URL($step, array $params = array()) {
	$params['step']         = intval($step);
	$params['medz']         = 'medz';
	$params['back']         = $_GET['back'];
	$_SERVER['REQUEST_URI'] = explode('?', $_SERVER['REQUEST_URI']);
	$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI']['0'];
	$url  = '';
	$url .= ($_SERVER['REQUEST_SCHEME'] ? $_SERVER['REQUEST_SCHEME'] . ':' : '') . '//';
	$url .= $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$url .= ':' . ($_SERVER['SERVER_PORT'] ? $_SERVER['SERVER_PORT'] : '80');
	$url .= $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'];
	$url .= '?';
	foreach($params as $name => $value) {
		$url .= $name . '=' . urlencode($value);
		$url .= '&';
	}
	$url = explode('&', $url);
	$url = array_filter($url);
	$url = implode('&', $url);
	return $url;
}

// #页面跳转方法~
function jump($message, $step, array $params = array()) {
	$url = URL($step, $params);
	show($message, $url);
}

// #显示消息
function show($message = '', $url = null) {
	include ROOT . 'show.php';
	exit;
}

function createFolder($path ='') {
	if ($path and !is_dir($path)) {
		createFolder(dirname($path));
		if (!@mkdir($path, 0777)) {
			return false;
		}
	}
	return true;
}

function clearRecur($dir, $delFolder = false) {
	if (!is_dir($dir)) return false;
	if (!$handle = @opendir($dir)) return false;
	while (false !== ($file = readdir($handle))) {
		if ('.' === $file || '..' === $file) continue;
		$_path = $dir . '/' . $file;
		if (is_dir($_path)) {
			clearRecur($_path, $delFolder);
		} elseif (is_file($_path))
			@unlink($_path);
	}
	@closedir($handle);
	$delFolder && @rmdir($dir);
	return true;
}