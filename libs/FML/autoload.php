<?php

/**
 * FancyManiaLinks - Automatic ManiaLink Generator Framework
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @version   1.4
 * @link      http://github.com/steeffeen/FancyManiaLinks
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
if (!defined('FML_PATH')) {
	define('FML_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}
if (!defined('FML_VERSION')) {
	define('FML_VERSION', '1.4');
}

/*
 * Autoload function that loads FML class files on demand
 */
if (!defined('FML_AUTOLOAD_DEFINED')) {
	define('FML_AUTOLOAD_DEFINED', true);
	spl_autoload_register(function ($className) {
		$classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className);
		$filePath  = FML_PATH . $classPath . '.php';
		if (file_exists($filePath)) {
			require_once $filePath;
		}
	});
}
