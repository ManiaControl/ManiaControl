<?php

// Enable error reporting
error_reporting(E_ALL);

// Run configuration
define('DEV_MODE', false); // Development mode to not send error reports etc.
define('LOG_NAME_USE_DATE', true); // Use current date as suffix for log file name in logs folder
define('LOG_NAME_USE_PID', true); // Use current process id as suffix for log file name in logs folder

// Define base dir
define('ManiaControlDir', __DIR__ . DIRECTORY_SEPARATOR);

// Define fatal error level
define('E_FATAL', E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_USER_ERROR);

// Min PHP Version
define('MIN_PHP_VERSION', '5.4');

// Set process settings
ini_set('memory_limit', '64M');
if (!ini_get('date.timezone') && function_exists('date_default_timezone_set')) {
	date_default_timezone_set('UTC');
}

// Make sure garbage collection is enabled
gc_enable();

// Register AutoLoader
require_once ManiaControlDir . 'core' . DIRECTORY_SEPARATOR . 'AutoLoader.php';
\ManiaControl\AutoLoader::register();

// Setup Logger
\ManiaControl\Logger::setup();

/**
 * Log and echo the given text
 *
 * @param string $message
 * @param bool   $eol
 */
function logMessage($message, $eol = true) {
	error_log($message);
	if ($eol) {
		$message = '[' . date('d-M-Y H:i:s e') . '] ' . $message . PHP_EOL;
	}
	echo $message;
}

logMessage('Starting ManiaControl...');

/**
 * Check for the requirements to run ManiaControl
 */
function checkRequirements() {
	// Check for min PHP version
	$phpVersion = phpversion();
	$message    = 'Checking for minimum required PHP-Version ' . MIN_PHP_VERSION . ' ... ';
	if ($phpVersion < MIN_PHP_VERSION) {
		logMessage($message . $phpVersion . ' TOO OLD VERSION!');
		logMessage(' -- Make sure that you install at least PHP ' . MIN_PHP_VERSION . '!');
		exit();
	}
	logMessage($message . MIN_PHP_VERSION . ' OK!');

	// Check for MySQLi
	$message = 'Checking for installed MySQLi ... ';
	if (!extension_loaded('mysqli')) {
		logMessage($message . 'NOT FOUND!');
		logMessage(" -- You don't have MySQLi installed! Check: http://www.php.net/manual/en/mysqli.installation.php");
		exit();
	}
	logMessage($message . 'FOUND!');

	// Check for cURL
	$message = 'Checking for installed cURL ... ';
	if (!extension_loaded('curl')) {
		logMessage($message . 'NOT FOUND!');
		logMessage(" -- You don't have cURL installed! Check: http://www.php.net/manual/en/curl.installation.php");
		exit();
	}
	logMessage($message . 'FOUND!');
}

checkRequirements();

// Start ManiaControl
$maniaControl = new \ManiaControl\ManiaControl();
$maniaControl->run();
