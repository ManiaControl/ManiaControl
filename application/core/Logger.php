<?php

namespace ManiaControl;

/**
 * ManiaControl Logger Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Logger {

	/**
	 * Setup the Logging Mechanism
	 */
	public static function setup() {
		$logFileName = ManiaControlDir . 'logs' . DIRECTORY_SEPARATOR;
		if (!is_dir($logFileName) && !mkdir($logFileName)) {
			echo "Couldn't create Logs Folder, please check the File Permissions!";
		}
		$logFileName .= 'ManiaControl';
		if (LOG_NAME_USE_DATE) {
			$logFileName .= '_' . date('Y-m-d');
		}
		if (LOG_NAME_USE_PID) {
			$logFileName .= '_' . getmypid();
		}
		$logFileName .= '.log';
		ini_set('error_log', $logFileName);
	}

	/**
	 * Log and echo the given Error Message
	 *
	 * @param string $message
	 * @param bool   $eol
	 * @param bool   $output
	 */
	public static function logError($message, $eol = true, $output = true) {
		$message = '[ERROR] ' . $message;
		self::log($message, $eol, $output);
	}

	/**
	 * Log and output the given Message
	 *
	 * @param string $message
	 * @param bool   $eol
	 * @param bool   $output
	 */
	public static function log($message, $eol = true, $output = true) {
		error_log($message);
		if ($output) {
			self::output($message, $eol);
		}
	}

	/**
	 * Echo the given Message
	 *
	 * @param string $message
	 * @param bool   $eol
	 */
	public static function output($message, $eol = true) {
		if ($eol) {
			$message = '[' . date('d-M-Y H:i:s e') . '] ' . $message . PHP_EOL;
		}
		echo $message;
	}
}
