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
}
