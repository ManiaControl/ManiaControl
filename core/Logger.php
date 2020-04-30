<?php

namespace ManiaControl;

use ManiaControl\Files\FileUtil;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Utils\Formatter;

/**
 * ManiaControl Logger Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Logger implements UsageInformationAble {
	use UsageInformationTrait;

	/**
	 * Setup the logging mechanism
	 */
	public static function setup() {
		self::setupErrorLogFileName();
		self::cleanLogsFolder();
	}

	/**
	 * Set the error log file name
	 */
	private static function setupErrorLogFileName() {
		$logsFolder = self::getLogsFolder();
		if ($logsFolder) {
			$logFileName = $logsFolder . 'ManiaControl';
			if (!defined('LOG_NAME_USE_DATE') || LOG_NAME_USE_DATE) {
				$logFileName .= '_' . date('Y-m-d');
			}
			if (!defined('LOG_NAME_USE_PID') || LOG_NAME_USE_PID) {
				$logFileName .= '_' . getmypid();
			}
			$logFileName .= '.log';
			ini_set('error_log', $logFileName);
		}
	}

	/**
	 * Get the logs folder and create it if necessary
	 *
	 * @return string
	 */
	public static function getLogsFolder() {
		$logsFolder = MANIACONTROL_PATH . 'logs' . DIRECTORY_SEPARATOR;
		if (!is_dir($logsFolder) && !mkdir($logsFolder)) {
			self::logError("Couldn't create the logs folder!");
			return null;
		}
		if (!is_writeable($logsFolder)) {
			self::logError("ManiaControl doesn't have the necessary write rights for the logs folder!");
			return null;
		}
		return $logsFolder;
	}

	/**
	 * Delete old ManiaControl log files
	 *
	 * @return bool
	 */
	private static function cleanLogsFolder() {
		$logsFolderPath = self::getLogsFolder();
		return FileUtil::cleanDirectory($logsFolderPath);
	}

	/**
	 * Log and output the given Error message
	 *
	 * @param string $message
	 * @param bool   $stripCodes
	 * @param bool   $eol
	 */
	public static function logError($message, $stripCodes = false, $eol = true) {
		$message = '[ERROR] ' . $message;
		self::log($message, $stripCodes, $eol);
	}

	/**
	 * Log and output the given message
	 *
	 * @param string $message
	 * @param bool   $stripCodes
	 * @param bool   $eol
	 */
	public static function log($message, $stripCodes = false, $eol = true) {
		if ($stripCodes) {
			$message = Formatter::stripCodes($message);
		}
		error_log($message);
		self::output($message, $eol);
	}

	/**
	 * Echo the given message
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
	 * Log and output the given Info message
	 *
	 * @param string $message
	 * @param bool   $stripCodes
	 * @param bool   $eol
	 */
	public static function logInfo($message, $stripCodes = false, $eol = true) {
		$message = '[INFO] ' . $message;
		self::log($message, $stripCodes, $eol);
	}

	/**
	 * Log and output the given Warning message
	 *
	 * @param string $message
	 * @param bool   $stripCodes
	 * @param bool   $eol
	 */
	public static function logWarning($message, $stripCodes = false, $eol = true) {
		$message = '[WARNING] ' . $message;
		self::log($message, $stripCodes, $eol);
	}
}
