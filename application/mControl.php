<?php

namespace mControl;

define('mControl', __DIR__);

require_once __DIR__ . '/core/core.mControl.php';

// Set process settings
ini_set('memory_limit', '128M');
if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
	date_default_timezone_set(@date_default_timezone_get());
}

// Error handling
ini_set('log_errors', 1);
ini_set('error_reporting', -1);
ini_set('error_log', 'iControl_' . getmypid() . '.log');

// Start mControl
error_log('Loading mControl v' . mControl::VERSION . '!');

$mControl = new mControl();
$iControl->run(true);

?>
