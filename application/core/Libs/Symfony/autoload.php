<?php

if (!defined('EVENT_DISPATCHER_PATH')) {
	define('EVENT_DISPATCHER_PATH', __DIR__);
}


spl_autoload_register(
	function ($className) {
		$classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className);
		$filePath = EVENT_DISPATCHER_PATH . DIRECTORY_SEPARATOR . $classPath . '.php';
		if (file_exists($filePath)) {
			require_once $filePath;
		}
	});
