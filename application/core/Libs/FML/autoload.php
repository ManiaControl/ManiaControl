<?php

/**
 * FancyManiaLinks - Automatic ManiaLink Generator Framework
 *
 * @author    steeffeen
 * @version   1.2
 * @link      http://github.com/steeffeen/FancyManiaLinks
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
if (!defined('FML_PATH')) {
	define('FML_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
}
if (!defined('FML_VERSION')) {
	define('FML_VERSION', '1.2');
}
//define('FML_SIMPLE_CLASSES', true);

/*
 * Autoload Function that loads FML Class Files on Demand
 */
if (!defined('FML_AUTOLOAD_DEFINED')) {
	define('FML_AUTOLOAD_DEFINED', true);
	spl_autoload_register(function ($className) {
		$classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className);
		$filePath  = FML_PATH . $classPath . '.php';
		if (file_exists($filePath)) {
			// Load with FML namespace
			require_once $filePath;
		} else if (defined('FML_SIMPLE_CLASSES') && FML_SIMPLE_CLASSES) {
			// Load as simple class name
			if (!function_exists('loadSimpleClass')) {

				/**
				 * Load FML Class Files from the given Directory
				 *
				 * @param string $className     Class to load
				 * @param string $directory     Directory to open
				 * @param array  $excludes      File Names to ignore
				 * @param string $baseNamespace Base Namespace
				 * @return bool
				 */
				function loadSimpleClass($className, $directory, $excludes, $baseNamespace) {
					if ($dirHandle = opendir($directory)) {
						$classParts      = explode('\\', $className);
						$simpleClassName = end($classParts);
						$classFileName   = $simpleClassName . '.php';
						while ($fileName = readdir($dirHandle)) {
							if (in_array($fileName, $excludes)) {
								continue;
							}
							$filePath = $directory . $fileName;
							if (is_dir($filePath)) {
								$subDirectory = $filePath . DIRECTORY_SEPARATOR;
								$namespace    = $baseNamespace . $fileName . '\\';
								$success      = loadSimpleClass($className, $subDirectory, $excludes, $namespace);
								if ($success) {
									return true;
								}
								continue;
							}
							if (is_file($filePath)) {
								if ($fileName == $classFileName) {
									require_once $filePath;
									class_alias($baseNamespace . $simpleClassName, $className, false);
									return true;
								}
								continue;
							}
						}
						closedir($dirHandle);
					}
					return false;
				}
			}
			$excludes      = array('.', '..');
			$baseNamespace = 'FML\\';
			loadSimpleClass($className, FML_PATH . 'FML' . DIRECTORY_SEPARATOR, $excludes, $baseNamespace);
		}
	});
}
