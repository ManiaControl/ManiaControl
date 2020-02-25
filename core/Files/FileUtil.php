<?php

namespace ManiaControl\Files;

use ManiaControl\Logger;
use ManiaControl\Utils\Formatter;
use ManiaControl\Utils\WebReader;

/**
 * Files Utility Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class FileUtil {

	/**
	 * @deprecated
	 * @see \ManiaControl\Utils\WebReader::loadUrl()
	 */
	public static function loadFile($url) {
		$response = WebReader::getUrl($url);
		return $response->getContent();
	}

	/**
	 * Load Config XML File
	 *
	 * @param string $fileName
	 * @return \SimpleXMLElement
	 */
	public static function loadConfig($fileName) {
		$fileLocation = MANIACONTROL_PATH . 'configs' . DIRECTORY_SEPARATOR . $fileName;
		if (!file_exists($fileLocation)) {
			Logger::log("Config file doesn't exist! ({$fileName})");
			return null;
		}
		if (!is_readable($fileLocation)) {
			Logger::log("Config file isn't readable! Please check the file permissions. ({$fileName})");
			return null;
		}
		$configXml = @simplexml_load_file($fileLocation);
		if (!$configXml) {
			Logger::log("Config file isn't maintained properly! ({$fileName})");
			return null;
		}
		return $configXml;
	}

	/**
	 * Return file name cleared from special characters
	 *
	 * @param string $fileName
	 * @return string
	 */
	public static function getClearedFileName($fileName) {
		$fileName = Formatter::stripCodes($fileName);
		$fileName = Formatter::utf8($fileName);
		$fileName = preg_replace('/[^0-9A-Za-z\-\+\.\_\ ]/', null, $fileName);
		$fileName = preg_replace('/ /', '_', $fileName);
		return $fileName;
	}

	/**
	 * Delete the temporary folder if it's empty
	 *
	 * @return bool
	 */
	public static function deleteTempFolder() {
		return self::deleteFolder(self::getTempFolder());
	}

	/**
	 * Delete the given folder if it's empty
	 *
	 * @param string $folderPath
	 * @param bool   $onlyIfEmpty
	 * @return bool
	 */
	public static function deleteFolder($folderPath, $onlyIfEmpty = true) {
		if ($onlyIfEmpty && !self::isFolderEmpty($folderPath)) {
			return false;
		}
		return rmdir($folderPath);
	}

	/**
	 * Check if the given folder is empty
	 *
	 * @param string $folderPath
	 * @return bool
	 */
	public static function isFolderEmpty($folderPath) {
		if (!is_readable($folderPath) || !is_dir($folderPath)) {
			return false;
		}
		$files = scandir($folderPath);
		return (count($files) <= 2);
	}

	/**
	 * Get the temporary folder and create it if necessary
	 *
	 * @return string|bool
	 */
	public static function getTempFolder() {
		$tempFolder = MANIACONTROL_PATH . 'temp' . DIRECTORY_SEPARATOR;
		if (!is_dir($tempFolder) && !mkdir($tempFolder)) {
			trigger_error("Couldn't create the temp folder!");
			return false;
		}
		if (!is_writeable($tempFolder)) {
			trigger_error("ManiaControl doesn't have the necessary write rights for the temp folder!");
			return false;
		}
		return $tempFolder;
	}

	/**
	 * Check if ManiaControl has sufficient Access to write to Files in the given Directories
	 *
	 * @param mixed $directories
	 * @return bool
	 */
	public static function checkWritePermissions($directories) {
		if (!is_array($directories)) {
			$directories = array($directories);
		}
		foreach ($directories as $directory) {
			$dir = new \RecursiveDirectoryIterator(MANIACONTROL_PATH . $directory);
			foreach (new \RecursiveIteratorIterator($dir) as $fileName => $file) {
				if (self::isHiddenFile($fileName)) {
					continue;
				}
				if (!is_writable($fileName)) {
					Logger::log("Write access missing for file '{$fileName}'!");
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Clean the given directory by deleting old files
	 *
	 * @param string $directory
	 * @param float  $maxFileAgeInDays
	 * @param bool   $recursive
	 * @return bool
	 */
	public static function cleanDirectory($directory, $maxFileAgeInDays = 10., $recursive = false) {
		if (!$directory || !is_dir($directory) || !is_readable($directory)) {
			return false;
		}
		$dirHandle = opendir($directory);
		if (!is_resource($dirHandle)) {
			return false;
		}
		$directory = self::appendDirectorySeparator($directory);
		$time      = time();
		while ($fileName = readdir($dirHandle)) {
			$filePath = $directory . $fileName;
			if (!is_readable($filePath)) {
				continue;
			}
			if (is_dir($filePath) && $recursive) {
				// Directory
				self::cleanDirectory($filePath . DIRECTORY_SEPARATOR, $maxFileAgeInDays, $recursive);
			} else if (is_file($filePath)) {
				// File
				if (!is_writable($filePath)) {
					continue;
				}
				$fileModTime   = filemtime($filePath);
				$timeDeltaDays = ($time - $fileModTime) / (24. * 3600.);
				if ($timeDeltaDays > $maxFileAgeInDays) {
					unlink($filePath);
				}
			}
		}
		closedir($dirHandle);
		return true;
	}

	/**
	 * Append the directory separator to the given path if necessary
	 *
	 * @param string $path
	 * @return string
	 */
	public static function appendDirectorySeparator($path) {
		if (substr($path, -1, 1) !== DIRECTORY_SEPARATOR) {
			$path .= DIRECTORY_SEPARATOR;
		}
		return $path;
	}

	/**
	 * Check whether the given file name is a PHP file
	 *
	 * @param string $fileName
	 * @return bool
	 */
	public static function isPhpFileName($fileName) {
		$extension = substr($fileName, -4);
		return (strtolower($extension) === '.php');
	}


	/**
	 * Returns the file name of a path to a PHP file
	 *
	 * @param $path
	 * @return string
	 */
	public static function getFileName($path) {
		$className      = '';
		$splitNameSpace = explode(DIRECTORY_SEPARATOR, $path);
		if (is_array($splitNameSpace)) {
			$className = end($splitNameSpace);
		}

		$className = str_replace('.php', '', $className);

		return $className;
	}

	/**
	 * Checks if a File is Hidden
	 *
	 * @param $fileName
	 * @return bool
	 */
	public static function isHiddenFile($fileName) {
		return (substr($fileName, 0, 1) === '.');
	}

	/**
	 * Shortens a path.
	 * Opposed to realpath, it follows symbolic links.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function shortenPath($path) {
		$root = substr($path, 0, 1) === '/';
		$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
		$parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
		$absolutes = array();
		foreach ($parts as $part) {
			if ('.' === $part)
				continue;

			if ('..' === $part)
				array_pop($absolutes);
			else
				array_push($absolutes, $part);
		}
		$path = implode(DIRECTORY_SEPARATOR, $absolutes);
		if ($root)
			$path = '/'.$path;
		return $path;
	}
}
