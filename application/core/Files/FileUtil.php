<?php

namespace ManiaControl\Files;

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
		if (substr($buffer, 9, 3) != '200') {
			return null;
		}

		$result = explode("\r\n\r\n", $buffer, 2);
		if (count($result) < 2) {
			return null;
		}

		return $result[1];
	}

	/**
	 * Load the Config XML-File with the given Name
	 *
	 * @param string $fileName
	 * @return \DOMElement
	 */
	public static function loadConfig($fileName) {
		$fileLocation = ManiaControlDir . 'configs' . DIRECTORY_SEPARATOR . $fileName;
		if (!file_exists($fileLocation)) {
			logMessage("Config file doesn't exist! ('{$fileName}')");
			return null;
		}
		if (!is_readable($fileLocation)) {
			logMessage("Config file isn't readable! Please check the file permissions. ('{$fileName}')");
			return null;
		}
		$configFileContent = @file_get_contents($fileLocation);
		if (!$configFileContent) {
			trigger_error("Couldn't load config file! ('{$fileName}')");
			return null;
		}
		$domDocument = new \DOMDocument();
		if (!@$domDocument->loadXML($configFileContent)) {
			$message = "Config file isn't maintained properly! ('{$fileName}')";
			$error   = error_get_last();
			if ($error && stripos($error['message'], 'DOMDocument::loadXML()') === 0) {
				$message .= PHP_EOL . $error['message'];
			}
			trigger_error($message);
			return null;
		}
		return $domDocument->firstChild;
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
		return $fileName;
	}

	/**
	 * Delete the Temporary Folder if it's empty
	 *
	 * @return bool
	 */
	public static function removeTempFolder() {
		$tempFolder = self::getTempFolder(false);
		return @rmdir($tempFolder);
	}

	/**
	 * Get the Temporary Folder and create it if necessary
	 *
	 * @param bool $createIfNecessary
	 * @return string
	 */
	public static function getTempFolder($createIfNecessary = true) {
		$tempFolder = ManiaControlDir . 'temp' . DIRECTORY_SEPARATOR;
		if ($createIfNecessary && !is_dir($tempFolder)) {
			mkdir($tempFolder);
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
					$message = "Write-Access missing for File '{$fileName}'!";
					logMessage($message);
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Try to delete the given Plugin Files
	 *
	 * @param mixed $pluginFileNames
	 */
	public static function cleanPluginFiles($pluginFileNames) {
		$pluginFileNames = (array)$pluginFileNames;
		$fileNames       = array();
		foreach ($pluginFileNames as $pluginFileName) {
			$fileName = 'plugins' . DIRECTORY_SEPARATOR . $pluginFileName;
			array_push($fileNames, $fileName);
		}
		self::cleanFiles($fileNames);
	}

	/**
	 * Try to delete the given Files
	 *
	 * @param mixed $fileNames
	 */
	public static function cleanFiles($fileNames) {
		$fileNames = (array)$fileNames;
		foreach ($fileNames as $fileName) {
			$filePath = ManiaControlDir . $fileName;
			if (file_exists($filePath) && is_writeable($filePath)) {
				unlink($filePath);
			}
		}
	}
}
