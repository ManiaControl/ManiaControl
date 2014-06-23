<?php

namespace ManiaControl\Files;

use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Utils\Formatter;

/**
 * Files Utility Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class FileUtil {

	/**
	 * Load a remote file
	 *
	 * @param string $url
	 * @param string $contentType
	 * @return string
	 */
	public static function loadFile($url, $contentType = 'UTF-8') {
		if (!$url) {
			return null;
		}
		$urlData  = parse_url($url);
		$host     = $urlData['host'];
		$port     = (isset($urlData['port']) ? $urlData['port'] : 80);
		$urlQuery = (isset($urlData['query']) ? '?' . $urlData['query'] : '');

		$fsock = fsockopen($host, $port);
		if (!is_resource($fsock)) {
			trigger_error("Couldn't open socket connection to '{$host}' on port '{$port}'!");
			return null;
		}
		stream_set_timeout($fsock, 3);

		$query = 'GET ' . $urlData['path'] . $urlQuery . ' HTTP/1.0' . PHP_EOL;
		$query .= 'Host: ' . $host . PHP_EOL;
		$query .= 'Content-Type: ' . $contentType . PHP_EOL;
		$query .= 'User-Agent: ManiaControl v' . ManiaControl::VERSION . PHP_EOL;
		$query .= PHP_EOL;

		fwrite($fsock, $query);

		$buffer = '';
		$info   = array('timed_out' => false);
		while (!feof($fsock) && !$info['timed_out']) {
			$buffer .= fread($fsock, 1024);
			$info = stream_get_meta_data($fsock);
		}
		fclose($fsock);

		if ($info['timed_out'] || !$buffer) {
			return null;
		}
		if (substr($buffer, 9, 3) !== '200') {
			return null;
		}

		$result = explode("\r\n\r\n", $buffer, 2);
		if (count($result) < 2) {
			return null;
		}

		return $result[1];
	}

	/**
	 * Load Config XML File
	 *
	 * @param string $fileName
	 * @return \SimpleXMLElement
	 */
	public static function loadConfig($fileName) {
		$fileLocation = ManiaControlDir . 'configs' . DIRECTORY_SEPARATOR . $fileName;
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
		$fileName = str_replace(array(DIRECTORY_SEPARATOR, '\\', '/', ':', '*', '?', '"', '<', '>', '|'), '_', $fileName);
		$fileName = preg_replace('/[^[:print:]]/', '', $fileName);
		$fileName = Formatter::utf8($fileName);
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
		$tempFolder = ManiaControlDir . 'temp' . DIRECTORY_SEPARATOR;
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
			$dir = new \RecursiveDirectoryIterator(ManiaControlDir . $directory);
			foreach (new \RecursiveIteratorIterator($dir) as $fileName => $file) {
				if (substr($fileName, 0, 1) === '.') {
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
		if (!is_dir($directory) || !is_readable($directory)) {
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
}
