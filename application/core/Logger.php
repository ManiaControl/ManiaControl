<?php

namespace ManiaControl;

use ManiaControl\Files\FileUtil;

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
		self::cleanLogsFolder();
	}

	/**
	 * Set the Error Log File Name
	 */
	private static function setupErrorLogFileName() {
		$logsFolder = self::getLogsFolder();
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
	 * Get the Logs Folder and create it if necessary
	 *
	 * @return string|bool
	 */
	public static function getLogsFolder() {
		$logsFolder = ManiaControlDir . 'logs' . DIRECTORY_SEPARATOR;
		if (!is_dir($logsFolder) && !mkdir($logsFolder)) {
			trigger_error("Couldn't create the logs folder!");
			return false;
		}
		if (!is_writeable($logsFolder)) {
			trigger_error("ManiaControl doesn't have the necessary write rights for the logs folder!");
			return false;
		}
		return $logsFolder;
	}

	/**
	 * Delete old ManiaControl Log Files
	 *
	 * @return bool
	 */
	private static function cleanLogsFolder() {
		$logsFolderPath = self::getLogsFolder();
		return FileUtil::cleanDirectory($logsFolderPath);
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
