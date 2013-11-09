<?php

namespace mControl;

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
	 * Check if the given data describes a player
	 *
	 * @param array $player        	
	 * @return bool
	 */
	public static function isPlayer($player) {
		if (!$player || !is_array($player)) return false;
		if (!array_key_exists('PlayerId', $player) || !is_int($player['PlayerId']) || $player['PlayerId'] <= 0) return false;
		return true;
	}

	/**
	 * Convert the given time int to mysql timestamp
	 *
	 * @param int $time        	
	 * @return string
	 */
	public static function timeToTimestamp($time) {
		return date("Y-m-d H:i:s", $time);
	}

	/**
	 * Add alignment attributes to an xml element
	 *
	 * @param simple_xml_element $xml        	
	 * @param string $halign        	
	 * @param string $valign        	
	 */
	public static function addAlignment($xml, $halign = 'center', $valign = 'center2') {
		if (!is_object($xml) || !method_exists($xml, 'addAttribute')) return;
		if (!property_exists($xml, 'halign')) $xml->addAttribute('halign', $halign);
		if (!property_exists($xml, 'valign')) $xml->addAttribute('valign', $valign);
	}

	/**
	 * Add translate attribute to an xml element
	 *
	 * @param simple_xml_element $xml        	
	 * @param bool $translate        	
	 */
	public static function addTranslate($xml, $translate = true) {
		if (!is_object($xml) || !method_exists($xml, 'addAttribute')) return;
		if (!property_exists($xml, 'translate')) $xml->addAttribute('translate', ($translate ? 1 : 0));
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
		$query .= 'User-Agent: mControl v' . mControl::VERSION . PHP_EOL;
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
	 * Formats the given time (milliseconds)
	 *
	 * @param int $time        	
	 * @return string
	 */
	public static function formatTime($time) {
		if (!is_int($time)) $time = (int) $time;
		$milliseconds = $time % 1000;
		$seconds = floor($time / 1000);
		$minutes = floor($seconds / 60);
		$hours = floor($minutes / 60);
		$minutes -= $hours * 60;
		$seconds -= $hours * 60 + $minutes * 60;
		$format = ($hours > 0 ? $hours . ':' : '');
		$format .= ($hours > 0 && $minutes < 10 ? '0' : '') . $minutes . ':';
		$format .= ($seconds < 10 ? '0' : '') . $seconds . ':';
		$format .= ($milliseconds < 100 ? '0' : '') . ($milliseconds < 10 ? '0' : '') . $milliseconds;
		return $format;
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
	 * Converts the given boolean to an int representation
	 *
	 * @param bool $bool        	
	 * @return int
	 */
	public static function boolToInt($bool) {
		return ($bool ? 1 : 0);
	}

	/**
	 * Build new simple xml element
	 *
	 * @param string $name        	
	 * @param string $id        	
	 * @return \SimpleXMLElement
	 */
	public static function newManialinkXml($id = null) {
		$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><manialink/>');
		$xml->addAttribute('version', '1');
		if ($id) $xml->addAttribute('id', $id);
		return $xml;
	}

	/**
	 * Load config xml-file
	 *
	 * @param string $fileName        	
	 * @return \SimpleXMLElement
	 */
	public static function loadConfig($fileName) {
		// Load config file from configs folder
		$fileLocation = mControl . '/configs/' . $fileName;
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
