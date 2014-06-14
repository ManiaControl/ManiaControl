<?php

namespace ManiaControl\Utils;

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
	public static function getDisabledFunctions() {
		$disabledText = ini_get('disable_functions');
		return explode(',', $disabledText);
	}
}
