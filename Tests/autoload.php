<?php
// Define base dir
define('MANIACONTROL_PATH',  realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
define('DEV_MODE', true); // Development mode to not send error reports etc.

define('PHP_UNIT_TEST', true);

// Register AutoLoader
require_once MANIACONTROL_PATH . 'core' . DIRECTORY_SEPARATOR . 'AutoLoader.php';
\ManiaControl\AutoLoader::register();