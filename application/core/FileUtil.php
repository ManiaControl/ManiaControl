<?php

namespace ManiaControl;

/**
 * File utility class
 *
 * @author steeffeen & kremsy
 */
abstract class FileUtil {

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
		
		$fsock = fsockopen($urlData['host'], $port);
		stream_set_timeout($fsock, 3);
		
		$query = 'GET ' . $urlData['path'] . ' HTTP/1.0' . PHP_EOL;
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
			trigger_error("Config file isn't readable! ({$fileName})");
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
		return str_replace(array('\\', '/', ':', '*', '?', '"', '<', '>', '|'), '_', $fileName);
	}
}
