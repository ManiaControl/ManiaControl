<?php

namespace ManiaControl\Utils;

use ManiaControl\Logger;

/**
 * System Utility Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class SystemUtil {
	/*
	 * Constants
	 */
	const OS_UNIX         = 'Unix';
	const OS_WIN          = 'Windows';
	const MIN_PHP_VERSION = '5.4';

	/**
	 * Get whether ManiaControl is running on Windows
	 *
	 * @return bool
	 */
	public static function isWindows() {
		return (self::getOS() === self::OS_WIN);
	}

	/**
	 * Get the Operating System on which ManiaControl is running
	 *
	 * @return string
	 */
	public static function getOS() {
		if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
			return self::OS_WIN;
		}
		return self::OS_UNIX;
	}

	/**
	 * Check for the requirements to run ManiaControl
	 */
	public static function checkRequirements() {
		$success = true;

		// Check for min PHP version
		$phpVersion = phpversion();
		$message    = 'Checking for minimum required PHP-Version ' . self::MIN_PHP_VERSION . ' ... ';
		if ($phpVersion < self::MIN_PHP_VERSION) {
			Logger::log($message . $phpVersion . ' TOO OLD VERSION!');
			Logger::log(' -- Make sure that you install at least PHP ' . self::MIN_PHP_VERSION . '!');
			$success = false;
		} else {
			Logger::log($message . $phpVersion . ' OK!');
		}

		// Check for MySQLi
		$message = 'Checking for installed MySQLi ... ';
		if (!extension_loaded('mysqli')) {
			Logger::log($message . 'NOT FOUND!');
			Logger::log(" -- You don't have MySQLi installed! Check: http://www.php.net/manual/en/mysqli.installation.php");
			$success = false;
		} else {
			Logger::log($message . 'FOUND!');
		}

		// Check for cURL
		$message = 'Checking for installed cURL ... ';
		if (!extension_loaded('curl')) {
			Logger::log($message . 'NOT FOUND!');
			Logger::log(" -- You don't have cURL installed! Check: http://www.php.net/manual/en/curl.installation.php");
			$success = false;
		} else {
			Logger::log($message . 'FOUND!');
		}

		// Check for ZIP
		$message = 'Checking for installed PHP ZIP ... ';
		if (!extension_loaded('zip')) {
			Logger::log($message . 'NOT FOUND!');
			Logger::log(" -- You don't have php-zip installed! Check: http://at1.php.net/manual/en/zip.installation.php");
			$success = false;
		} else {
			Logger::log($message . 'FOUND!');
		}

		// Check for Zlib
		$message = 'Checking for installed Zlib ... ';
		if (!extension_loaded('zlib')) {
			Logger::log($message . 'NOT FOUND!');
			Logger::log(" -- You don't have Zlib installed! Check: http://php.net/manual/en/zlib.setup.php");
			$success = false;
		} else {
			Logger::log($message . 'FOUND!');
		}

		// Check for MBString
		$message = 'Checking for installed mbstring ... ';
		if (!extension_loaded('mbstring')) {
			Logger::log($message . 'NOT FOUND!');
			Logger::log(" -- You don't have mbstring installed! Check: http://php.net/manual/en/mbstring.setup.php");
			$success = false;
		} else {
			Logger::log($message . 'FOUND!');
		}

		if (!$success) {
			// Missing requirements
			self::quit();
		}
	}

	/**
	 * Stop ManiaControl immediately
	 *
	 * @param string $message
	 * @param bool   $errorPrefix
	 */
	public static function quit($message = null, $errorPrefix = false) {
		if ($message) {
			if ($errorPrefix) {
				Logger::logError($message);
			} else {
				Logger::log($message);
			}
		}

		if (!defined('PHP_UNIT_TEST')) {
			exit;
		}
	}

	/**
	 * Reboot ManiaControl immediately
	 */
	public static function reboot() {
		if (SystemUtil::isUnix()) {
			self::rebootUnix();
		} else {
			self::rebootWindows();
		}
	}

	/**
	 * Get whether ManiaControl is running on Unix
	 *
	 * @return bool
	 */
	public static function isUnix() {
		return (self::getOS() === self::OS_UNIX);
	}

	/**
	 * Perform restart on Unix
	 */
	private static function rebootUnix() {
		if (!SystemUtil::checkFunctionAvailability('exec')) {
			Logger::log("Can't reboot ManiaControl because the function 'exec' is disabled!");
			return;
		}
		$fileName = null;
		if ($scriptName = CommandLineHelper::getParameter('-sh')) {
			$fileName = $scriptName;
		} else {
			$fileName = 'ManiaControl.sh';
		}
		$filePath = MANIACONTROL_PATH . $fileName;
		if (!is_readable($filePath)) {
			Logger::log("Can't reboot ManiaControl because the file '{$fileName}' doesn't exist or isn't readable!");
			return;
		}
		$command = 'sh ' . escapeshellarg($filePath) . ' > /dev/null &';
		exec($command);
	}

	/**
	 * Check whether the given function is available
	 *
	 * @param string $functionName
	 * @return bool
	 */
	public static function checkFunctionAvailability($functionName) {
		return (function_exists($functionName) && !in_array($functionName, self::getDisabledFunctions()));
	}

	/**
	 * Get the array of disabled functions
	 *
	 * @return array
	 */
	protected static function getDisabledFunctions() {
		$disabledText = ini_get('disable_functions');
		return explode(',', $disabledText);
	}

	/**
	 * Perform reboot on Windows
	 */
	private static function rebootWindows() {
		if (!SystemUtil::checkFunctionAvailability('system')) {
			Logger::log("Can't reboot ManiaControl because the function 'system' is disabled!");
			return;
		}
		$fileName = null;
		if ($scriptName = CommandLineHelper::getParameter('-bat')) {
			$fileName = $scriptName;
		} else {
			$fileName = 'ManiaControl.bat';
		}
		$filePath = MANIACONTROL_PATH . $fileName;
		if (!is_readable($filePath)) {
			Logger::log("Can't reboot ManiaControl because the file '{$fileName}' doesn't exist or isn't readable!");
			return;
		}
		$command = escapeshellarg($filePath);
		system($command); // TODO: windows stops here as long as controller is running
	}
}
