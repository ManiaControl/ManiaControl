<?php

// Run configuration

define('LOG_WRITE_CURRENT_FILE', 'ManiaControl.log'); // Write current log to extra file in base dir
define('LOG_NAME_USE_DATE', true); // Use current date as suffix for log file name in logs folder
define('LOG_NAME_USE_PID', true); // Use current process id as suffix for log file name in logs folder

// Define base dir
define('ManiaControlDir', __DIR__);

// Set process settings
ini_set('memory_limit', '64M');
if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
	date_default_timezone_set(@date_default_timezone_get());
}

// Build log file name
$logFileName = ManiaControlDir . '/logs/';
if (!is_dir($logFileName)) {
	mkdir($logFileName);
}
$logFileName .= '/ManiaControl';
if (LOG_NAME_USE_DATE) {
	$logFileName .= '_' . date('Y-m-d');
}
if (LOG_NAME_USE_PID) {
	$logFileName .= '_' . getmypid();
}
$logFileName .= '.log';
define('LOG_FILE', $logFileName);

// Delete old current log file
if (LOG_WRITE_CURRENT_FILE) {
	$currentLogFileName = ManiaControlDir . '/' . LOG_WRITE_CURRENT_FILE;
	if (file_exists($currentLogFileName) && is_writable($currentLogFileName)) {
		unlink($currentLogFileName);
	}
	define('LOG_CURRENT_FILE', $currentLogFileName);
}

/**
 * Log and echo the given text
 *
 * @param string $message
 */
function logMessage($message) {
	$message .= PHP_EOL;
	if (defined('LOG_CURRENT_FILE')) {
		file_put_contents(LOG_CURRENT_FILE, $message, FILE_APPEND);
	}
	file_put_contents(LOG_FILE, $message, FILE_APPEND);
	echo $message;
}


// Autoload Function that loads ManiaControl Class Files on Demand
spl_autoload_register(function ($className) {
	$classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className);
	$classPath = preg_replace('/ManiaControl/', 'core', $classPath, 1);
	$filePath  = ManiaControlDir . DIRECTORY_SEPARATOR . $classPath . '.php';
	if (file_exists($filePath)) {
		require_once $filePath;
	}
});

// Start ManiaControl
$maniaControl = new \ManiaControl\ManiaControl();
$maniaControl->run();
