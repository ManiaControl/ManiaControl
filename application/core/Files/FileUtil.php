<?php

namespace ManiaControl\Files;

use ManiaControl\ManiaControl;
use ManiaControl\Formatter;

/**
 * Files Utility Class
 * 
 * @author steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class FileUtil {
	/*
	 * Constants
	 */
	const FOLDER_NAME_TEMP = '/temp/';

	/**
	 * Load a remote file
	 * 
	 * @param string $url
	 * @param string $contentType
	 * @return string || null
	 */
	public static function loadFile($url, $contentType = 'UTF-8') {
		if (!$url) {
			return null;
		}
		$urlData = parse_url($url);
		$port = (isset($urlData['port']) ? $urlData['port'] : 80);
		$urlQuery = isset($urlData['query']) ? "?" . $urlData['query'] : "";
		
		$fsock = fsockopen($urlData['host'], $port);
		stream_set_timeout($fsock, 3);
		
		$query = 'GET ' . $urlData['path'] . $urlQuery . ' HTTP/1.0' . PHP_EOL;
		$query .= 'Host: ' . $urlData['host'] . PHP_EOL;
		$query .= 'Content-Type: ' . $contentType . PHP_EOL;
		$query .= 'User-Agent: ManiaControl v' . ManiaControl::VERSION . PHP_EOL;
		$query .= PHP_EOL;
		
		fwrite($fsock, $query);
		
		$buffer = '';
		$info = array('timed_out' => false);
		while (!feof($fsock) && !$info['timed_out']) {
			$buffer .= fread($fsock, 1024);
			$info = stream_get_meta_data($fsock);
		}
		fclose($fsock);
		
		if ($info['timed_out'] || !$buffer) {
			return null;
		}
		if (substr($buffer, 9, 3) != "200") {
			return null;
		}
		
		$result = explode("\r\n\r\n", $buffer, 2);
		
		if (count($result) < 2) {
			return null;
		}
		
		return $result[1];
	}

	/**
	 * Load config xml-file
	 * 
	 * @param string $fileName
	 * @return \SimpleXMLElement
	 */
	public static function loadConfig($fileName) {
		$fileLocation = ManiaControlDir . '/configs/' . $fileName;
		if (!file_exists($fileLocation)) {
			trigger_error("Config file doesn't exist! ({$fileName})");
			return null;
		}
		if (!is_readable($fileLocation)) {
			trigger_error("Config file isn't readable! Please check your file permissions. ({$fileName})");
			return null;
		}
		return simplexml_load_file($fileLocation);
	}

	/**
	 * Return file name cleared from special characters
	 * 
	 * @param string $fileName
	 * @return string
	 */
	public static function getClearedFileName($fileName) {
		$fileName = Formatter::stripCodes($fileName);
		$fileName = str_replace(array('\\', '/', ':', '*', '?', '"', '<', '>', '|'), '_', $fileName);
		$fileName = preg_replace('/[^[:print:]]/', '', $fileName);
		return $fileName;
	}

	/**
	 * Get the Temporary Folder and create it if necessary
	 * 
	 * @param bool $createIfNecessary
	 * @return string
	 */
	public static function getTempFolder($createIfNecessary = true) {
		$tempFolder = ManiaControlDir . self::FOLDER_NAME_TEMP;
		if ($createIfNecessary && !is_dir($tempFolder)) {
			mkdir($tempFolder);
		}
		return $tempFolder;
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
}
