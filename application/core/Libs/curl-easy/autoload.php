<?php

if (!defined('cURL_PATH')) {
	define('cURL_PATH', __DIR__);
}

spl_autoload_register(
	function ($className) {
		$classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className);
		$filePath = cURL_PATH . DIRECTORY_SEPARATOR . $classPath . '.php';
		if (file_exists($filePath)) {
			require_once $filePath;
		}
	});
