<?php

namespace ManiaControl;

use ManiaControl\Files\FileUtil;
use ManiaControl\Utils\Formatter;

/**
 * ManiaControl Logger Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Logger {

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
			self::logError("Couldn't create the logs folder!");
			return false;
		}
		if (!is_writeable($logsFolder)) {
			self::logError("ManiaControl doesn't have the necessary write rights for the logs folder!");
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
	 * @param bool   $stripCodes
	 */
	public static function logError($message, $eol = true, $stripCodes = false) {
		$message = '[ERROR] ' . $message;
		self::log($message, $eol, $stripCodes);
	}

	/**
	 * Log and output the given Message
	 *
	 * @param string $message
	 * @param bool   $eol
	 * @param bool   $stripCodes
	 */
	public static function log($message, $eol = true, $stripCodes = false) {
		if ($stripCodes) {
			$message = Formatter::stripCodes($message);
		}
		error_log($message);
		self::output($message, $eol);
	}

	/**
	 * Echo the given Message
	 *
	 * @param string $message
	 * @param bool   $eol
	 */
	private static function output($message, $eol = true) {
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
	 * @param bool   $stripCodes
	 */
	public static function logInfo($message, $eol = true, $stripCodes = false) {
		$message = '[INFO] ' . $message;
		self::log($message, $eol, $stripCodes);
	}

	/**
	 * Log and echo the given Warning Message
	 *
	 * @param string $message
	 * @param bool   $eol
	 * @param bool   $stripCodes
	 */
	public static function logWarning($message, $eol = true, $stripCodes = false) {
		$message = '[WARNING] ' . $message;
		self::log($message, $eol, $stripCodes);
	}
}
