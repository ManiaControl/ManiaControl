<?php

// Enable error reporting
error_reporting(E_ALL);

// Run configuration
define('DEV_MODE', false); // Development mode to not send error reports etc.
define('LOG_NAME_USE_DATE', true); // Use current date as suffix for log file name in logs folder
define('LOG_NAME_USE_PID', true); // Use current process id as suffix for log file name in logs folder

// Define base dir
define('ManiaControlDir', __DIR__ . DIRECTORY_SEPARATOR);

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
 * @deprecated
 * @see \ManiaControl\Logger::log()
 */
function logMessage($message, $eol = true) {
	\ManiaControl\Logger::log($message, $eol);
}

\ManiaControl\Logger::log('Starting ManiaControl...');

// Check requirements
\ManiaControl\Utils\SystemUtil::checkRequirements();

// Start ManiaControl
$maniaControl = new \ManiaControl\ManiaControl();
$maniaControl->run();
