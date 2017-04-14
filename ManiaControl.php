<?php
/**
 * ManiaControl Server Controller for ManiaPlanet Server
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */

// Enable error reporting
error_reporting(E_ALL);

// Run configuration
define('DEV_MODE', false); // Development mode to not send error reports etc.
define('LOG_NAME_USE_DATE', true); // Use current date as suffix for log file name in logs folder
define('LOG_NAME_USE_PID', true); // Use current process id as suffix for log file name in logs folder

// Define base dir
define('MANIACONTROL_PATH', __DIR__ . DIRECTORY_SEPARATOR);
/** @deprecated Use MANIACONTROL_PATH */
define('ManiaControlDir', MANIACONTROL_PATH);

// Set process settings
ini_set('memory_limit', '64M');
if (!ini_get('date.timezone') && function_exists('date_default_timezone_set')) {
	date_default_timezone_set('UTC');
}

// Make sure garbage collection is enabled
gc_enable();

// Register AutoLoader
require_once MANIACONTROL_PATH . 'core' . DIRECTORY_SEPARATOR . 'AutoLoader.php';
\ManiaControl\AutoLoader::register();

// Setup Logger
\ManiaControl\Logger::setup();

\ManiaControl\Logger::log('Starting ManiaControl...');

// Check requirements
\ManiaControl\Utils\SystemUtil::checkRequirements();

// Start ManiaControl
$maniaControl = new \ManiaControl\ManiaControl();
$maniaControl->run();
