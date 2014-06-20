<?php

namespace ManiaControl;

/**
 * ManiaControl AutoLoader
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AutoLoader {

	/**
	 * Register the Auto Loader
	 */
	public static function register() {
		spl_autoload_register(array(get_class(), 'autoload'));
	}
	/**
	 * Try to autoload the Class with the given Name
	 *
	 * @param string $className
	 */
	public static function autoload($className) {
		$classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className);

		// Core file
		$classDirectoryPath = preg_replace('/ManiaControl/', 'core', $classPath, 1);
		$filePath           = ManiaControlDir . $classDirectoryPath . '.php';
		if (file_exists($filePath)) {
			require_once $filePath;
			return;
		}

		// Plugin file
		$filePath = ManiaControlDir . 'plugins' . DIRECTORY_SEPARATOR . $classPath . '.php';
		if (file_exists($filePath)) {
			include_once $filePath;
			return;
		}
	}
}
