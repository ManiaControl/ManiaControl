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
		self::setupErrorLogFileName();
	}

	/**
	 * Set the Error Log File Name
	 */
	private static function setupErrorLogFileName() {
		$logsFolder = self::createLogsFolder();
		if ($logsFolder) {
			$logFileName = $logsFolder . 'ManiaControl';
			if (LOG_NAME_USE_DATE) {
				$logFileName .= '_' . date('Y-m-d');
			}
			if (LOG_NAME_USE_PID) {
				$logFileName .= '_' . getmypid();
			}
			$logFileName .= '.log';
			ini_set('error_log', $logFileName);
		}
	}

	/**
	 * Create the Logs Folder and return its Path if successful
	 *
	 * @return bool|string
	 */
	private static function createLogsFolder() {
		$logsFolderPath = self::getLogsFolderPath();
		if (!is_dir($logsFolderPath) && !mkdir($logsFolderPath)) {
			self::output("Couldn't create Logs Folder, please check the File Permissions!");
			return false;
		}
		return $logsFolderPath;
	}

	/**
	 * Build the Logs Folder Path
	 *
	 * @return string
	 */
	public static function getLogsFolderPath() {
		return ManiaControlDir . 'logs' . DIRECTORY_SEPARATOR;
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
	 * Log and echo the given Info Message
	 *
	 * @param string $message
	 * @param bool   $eol
	 * @param bool   $output
	 */
	public static function logInfo($message, $eol = true, $output = true) {
		$message = '[INFO] ' . $message;
		self::log($message, $eol, $output);
	}

	/**
	 * Log and echo the given Warning Message
	 *
	 * @param string $message
	 * @param bool   $eol
	 * @param bool   $output
	 */
	public static function logWarning($message, $eol = true, $output = true) {
		$message = '[WARNING] ' . $message;
		self::log($message, $eol, $output);
	}
}
