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
if (LOG_NAME_USE_DATE) $logFileName .= '_' . date('Y-m-d');
if (LOG_NAME_USE_PID) $logFileName .= '_' . getmypid();
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

// Log function
function logMessage($message) {
	$message .= PHP_EOL;
	if (defined('LOG_CURRENT_FILE')) file_put_contents(LOG_CURRENT_FILE, $message, FILE_APPEND);
	file_put_contents(LOG_FILE, $message, FILE_APPEND);
	echo $message;
}

// Error level parse function
function getErrorTag($errorLevel) {
	if ($errorLevel == E_NOTICE) {
		return '[PHP NOTICE]';
	}
	if ($errorLevel == E_WARNING) {
		return '[PHP WARNING]';
	}
	if ($errorLevel == E_ERROR) {
		return '[PHP ERROR]';
	}
	if ($errorLevel == E_USER_NOTICE) {
		return '[ManiaControl NOTICE]';
	}
	if ($errorLevel == E_USER_WARNING) {
		return '[ManiaControl WARNING]';
	}
	if ($errorLevel == E_USER_ERROR) {
		return '[ManiaControl ERROR]';
	}
	return "[PHP {$errorLevel}]";
}

// Register error handler
set_error_handler(
		function ($errorNumber, $errorString, $errorFile, $errorLine) {
			if (error_reporting() == 0) {
				// Error suppressed
				return false;
			}
			// Log error
			$errorTag = getErrorTag($errorNumber);
			$message = "{$errorTag}: {$errorString} in File '{$errorFile}' on Line {$errorLine}!";
			logMessage($message);
			if ($errorNumber == E_ERROR || $errorNumber == E_USER_ERROR) {
				logMessage('Stopping execution...');
				exit();
			}
			return false;
		}, -1);

// Start ManiaControl
require_once __DIR__ . '/core/ManiaControl.php';
$maniaControl = new ManiaControl\ManiaControl();
$maniaControl->run();
