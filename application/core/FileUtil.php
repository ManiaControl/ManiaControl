<?php

namespace ManiaControl;

/**
 * File utility class
 *
 * @author steeffeen & kremsy
 */
abstract class FileUtil {

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
