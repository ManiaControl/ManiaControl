<?php

namespace ManiaControl;

define('ManiaControlDir', __DIR__);

require_once __DIR__ . '/core/core.php';

// Set process settings
ini_set('memory_limit', '128M');
if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
	date_default_timezone_set(@date_default_timezone_get());
}

// Error handling
ini_set('log_errors', 1);
ini_set('error_reporting', -1);
ini_set('error_log', 'ManiaControl_' . getmypid() . '.log');

// Start ManiaControl
error_log('Loading ManiaControl v' . ManiaControl::VERSION . '!');

$maniaControl = new ManiaControl();
$maniaControl->run(true);

?>
