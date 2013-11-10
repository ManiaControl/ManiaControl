<?php

namespace ManiaControl;

/**
 * Class for basic tools
 *
 * @author steeffeen
 */
class Tools {

	/**
	 * Check if the given setting is enabled
	 *
	 * @param simple_xml_element $config        	
	 * @param string $setting        	
	 */
	public static function checkSetting($config, $setting) {
		$settings = $config->xpath('//' . $setting);
		if (empty($settings)) {
			return false;
		}
		else {
			foreach ($settings as $setting) {
				return self::toBool((string) $setting[0]);
			}
		}
	}

	/**
	 * Load a remote file
	 *
	 * @param string $url        	
	 * @return string || null
	 */
	public static function loadFile($url) {
		if (!$url) return false;
		$urlData = parse_url($url);
		$port = (isset($urlData['port']) ? $urlData['port'] : 80);
		
		$fsock = fsockopen($urlData['host'], $port);
		stream_set_timeout($fsock, 3);
		
		$query = 'GET ' . $urlData['path'] . ' HTTP/1.0' . PHP_EOL;
		$query .= 'Host: ' . $urlData['host'] . PHP_EOL;
		$query .= 'Content-Type: UTF-8' . PHP_EOL;
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
	 * Convert given data to real boolean
	 *
	 * @param
	 *        	mixed data
	 */
	public static function toBool($var) {
		if ($var === true) return true;
		if ($var === false) return false;
		if ($var === null) return false;
		if (is_object($var)) {
			$var = (string) $var;
		}
		if (is_int($var)) {
			return ($var > 0);
		}
		else if (is_string($var)) {
			$text = strtolower($var);
			if ($text === 'true' || $text === 'yes') {
				return true;
			}
			else if ($text === 'false' || $text === 'no') {
				return false;
			}
			else {
				return ((int) $text > 0);
			}
		}
		else {
			return (bool) $var;
		}
	}

	/**
	 * Load config xml-file
	 *
	 * @param string $fileName        	
	 * @return \SimpleXMLElement
	 */
	public static function loadConfig($fileName) {
		// Load config file from configs folder
		$fileLocation = ManiaControlDir . '/configs/' . $fileName;
		if (!file_exists($fileLocation)) {
			trigger_error("Config file doesn't exist! (" . $fileName . ")", E_USER_ERROR);
		}
		return simplexml_load_file($fileLocation);
	}

	/**
	 * Send the given manialink to players
	 *
	 * @param string $manialink        	
	 * @param array $logins        	
	 */
	public static function sendManialinkPage($client, $manialink, $logins = null, $timeout = 0, $hideOnClick = false) {
		if (!$client || !$manialink) return;
		if (!$logins) {
			// Send manialink to all players
			$client->query('SendDisplayManialinkPage', $manialink, $timeout, $hideOnClick);
		}
		else if (is_array($logins)) {
			// Send manialink to players
			foreach ($logins as $login) {
				$client->query('SendDisplayManialinkPageToLogin', $login, $manialink, $timeout, $hideOnClick);
			}
		}
		else if (is_string($logins)) {
			// Send manialink to player
			$client->query('SendDisplayManialinkPageToLogin', $logins, $manialink, $timeout, $hideOnClick);
		}
	}
}

?>
