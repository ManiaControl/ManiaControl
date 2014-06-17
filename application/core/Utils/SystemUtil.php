<?php

namespace ManiaControl\Utils;

use ManiaControl\Logger;

/**
 * System Utility Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class SystemUtil {
	/*
	 * Constants
	 */
	const OS_UNIX = 'Unix';
	const OS_WIN  = 'Windows';

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
	 * Get whether ManiaControl is running on Unix
	 *
	 * @return bool
	 */
	public static function isUnix() {
		return (self::getOS() === self::OS_UNIX);
	}

	/**
	 * Check whether the given Function is available
	 *
	 * @param string $functionName
	 * @return bool
	 */
	public static function checkFunctionAvailability($functionName) {
		return (function_exists($functionName) && !in_array($functionName, self::getDisabledFunctions()));
	}

	/**
	 * Get the Array of Disabled Functions
	 *
	 * @return array
	 */
	protected static function getDisabledFunctions() {
		$disabledText = ini_get('disable_functions');
		return explode(',', $disabledText);
	}

	/**
	 * Check for the Requirements to run ManiaControl
	 */
	public static function checkRequirements() {
		$success = true;

		// Check for min PHP version
		$phpVersion = phpversion();
		$message    = 'Checking for minimum required PHP-Version ' . MIN_PHP_VERSION . ' ... ';
		if ($phpVersion < MIN_PHP_VERSION) {
			Logger::log($message . $phpVersion . ' TOO OLD VERSION!');
			Logger::log(' -- Make sure that you install at least PHP ' . MIN_PHP_VERSION . '!');
			$success = false;
		} else {
			Logger::log($message . MIN_PHP_VERSION . ' OK!');
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

		if (!$success) {
			// Missing requirements
			exit;
		}
	}
}
