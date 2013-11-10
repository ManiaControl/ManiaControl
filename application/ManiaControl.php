<?php
use ManiaControl\ManiaControl;

// Define base dir
define('ManiaControlDir', __DIR__);

// Set process settings
ini_set('memory_limit', '128M');
if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
	date_default_timezone_set(@date_default_timezone_get());
}

// Error handling
ini_set('log_errors', 1);
ini_set('error_reporting', -1);
if (!is_dir('logs')) {
	mkdir('logs');
}
ini_set('error_log', 'logs/ManiaControl_' . getmypid() . '.log');

// Load ManiaControl class
require_once __DIR__ . '/core/maniaControl.php';

// Start ManiaControl
error_log('Loading ManiaControl v' . ManiaControl::VERSION . '...');

$maniaControl = new ManiaControl();
$maniaControl->run();

?>
