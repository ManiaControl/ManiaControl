<?php

namespace iControl;

define('ICONTROL', __DIR__);

require_once __DIR__ . '/core/core.iControl.php';

// Set process settings
ini_set('memory_limit', '128M');
if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
	date_default_timezone_set(@date_default_timezone_get());
}

// Error handling
ini_set('log_errors', 1);
ini_set('error_reporting', -1);
ini_set('error_log', 'iControl_' . getmypid() . '.log');

// Start iControl
error_log('Loading iControl v' . iControl::VERSION . '!');

$iControl = new iControl();
$iControl->run(true);

?>
