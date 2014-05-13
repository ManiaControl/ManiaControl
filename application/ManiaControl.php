<?php

// Run configuration
define('LOG_WRITE_CURRENT_FILE', 'ManiaControl.log'); // Write current log to extra file in base dir
define('LOG_NAME_USE_DATE', true); // Use current date as suffix for log file name in logs folder
define('LOG_NAME_USE_PID', true); // Use current process id as suffix for log file name in logs folder

// Define base dir
define('ManiaControlDir', __DIR__ . DIRECTORY_SEPARATOR);

// Enable error reporting
error_reporting(E_ALL);

// Define fatal error level
define('E_FATAL', E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_USER_ERROR);

// Set process settings
ini_set('memory_limit', '64M');
if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
	date_default_timezone_set(@date_default_timezone_get());
}

// Build log file name
$logFileName = ManiaControlDir . 'logs' . DIRECTORY_SEPARATOR;
if (!is_dir($logFileName) && !mkdir($logFileName)) {
	echo "Couldn't create Logs Folder, please check the File Permissions!";
}
$logFileName .= 'ManiaControl';
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
	$currentLogFileName = ManiaControlDir . LOG_WRITE_CURRENT_FILE;
	if (file_exists($currentLogFileName) && is_writable($currentLogFileName)) {
		unlink($currentLogFileName);
	}
	define('LOG_CURRENT_FILE', $currentLogFileName);
}

logMessage('Starting ManiaControl ...');

/** Check for Min PHP version */
define('MIN_PHP_VERSION', '5.4');
logMessage('Checking for minimum required PHP-Version ' . MIN_PHP_VERSION . ' ... ', false);
if (phpversion() >= MIN_PHP_VERSION) {
	logMessage(phpversion() . " OK!", true, false);
} else {
	logMessage('TOO OLD VERSION!', true, false);
	logMessage(' -- Make sure that you install at least PHP 5.4', true, false);
	exit();
}

/**
 * Checking if all the needed libraries are installed.
 * - MySQLi
 * - cURL
 */
logMessage('Checking for installed MySQLi ... ', false);
if (extension_loaded('mysqli')) {
	logMessage('FOUND!', true, false);
} else {
	logMessage('NOT FOUND!', true, false);
	logMessage(' -- You don\'t have MySQLi installed, make sure to check: http://www.php.net/manual/en/mysqli.installation.php', true, false);
	exit();
}

logMessage('Checking for installed cURL   ... ', false);
if (extension_loaded('curl')) {
	logMessage('FOUND!', true, false);
} else {
	logMessage('NOT FOUND!', true, false);
	logMessage('You don\'t have cURL installed, make sure to check: http://www.php.net/manual/en/curl.installation.php', true, false);
	exit();
}

/**
 * Log and echo the given text
 *
 * @param string $message
 * @param bool   $eol
 * @param bool   $date
 */
function logMessage($message, $eol = true, $date = true) {
	if ($date) {
		$date    = date('d.M y H:i:s');
		$message = $date . ' ' . $message;
	}

	if ($eol) {
		$message .= PHP_EOL;
	}

	if (defined('LOG_CURRENT_FILE')) {
		if (!is_writable(dirname(LOG_CURRENT_FILE)) || !file_put_contents(LOG_CURRENT_FILE, $message, FILE_APPEND)) {
			echo 'Current-Logfile not write-able, please check the File Permissions!';
		}
	}
	if (!is_writable(dirname(LOG_FILE)) || !file_put_contents(LOG_FILE, $message, FILE_APPEND)) {
		echo 'Logfile not write-able, please check the File Permissions!';
	}
	echo $message;
}

// Autoload Function that loads ManiaControl Class Files on Demand
spl_autoload_register(function ($className) {
	$classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className);

	// Core file
	$classDirectoryPath = preg_replace('/ManiaControl/', 'core', $classPath, 1);
	$filePath           = ManiaControlDir . $classDirectoryPath . '.php';
	if (file_exists($filePath)) {
		require_once $filePath;
		return;
	}

	// Plugin file
	$filePath = ManiaControlDir . 'plugins' . DIRECTORY_SEPARATOR . $classPath . '.php';
	if (file_exists($filePath)) {
		include_once $filePath;
		return;
	}
});

// Start ManiaControl
$maniaControl = new \ManiaControl\ManiaControl();
$maniaControl->run();
