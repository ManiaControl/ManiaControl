<?php

namespace ManiaControl\Manialinks;

require_once __DIR__ . '/../../FML/autoload.php';

/**
 * Manialink utility class
 *
 * @author steeffeen & kremsy
 */
class ManialinkUtil {

	/**
	 * Send the given manialink to players
	 *
	 * @param \IXR_ClientMulticall_Gbx $client        	
	 * @param string $manialink        	
	 * @param array $logins        	
	 * @param int $timeout        	
	 * @param bool $hideOnClick        	
	 * @return bool
	 */
	public static function sendManialinkPage(\IXR_ClientMulticall_Gbx $client, $manialinkText, array $logins = null, $timeout = 0, 
			$hideOnClick = false) {
		if (!$client || !$manialinkText) {
			return false;
		}
		if (!$logins) {
			return $client->query('SendDisplayManialinkPage', $manialinkText, $timeout, $hideOnClick);
		}
		if (is_string($logins)) {
			return $client->query('SendDisplayManialinkPageToLogin', $logins, $manialinkText, $timeout, $hideOnClick);
		}
		if (is_array($logins)) {
			$success = true;
			foreach ($logins as $login) {
				$subSuccess = $client->query('SendDisplayManialinkPageToLogin', $login, $manialinkText, $timeout, $hideOnClick);
				if (!$subSuccess) {
					$success = false;
				}
			}
			return $success;
		}
		return false;
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
		if ($id) {
			$xml->addAttribute('id', $id);
		}
		return $xml;
	}

	/**
	 * Add alignment attributes to an xml element
	 *
	 * @param \SimpleXMLElement $xml        	
	 * @param string $halign        	
	 * @param string $valign        	
	 */
	public static function addAlignment(\SimpleXMLElement $xml, $halign = 'center', $valign = 'center2') {
		if (!property_exists($xml, 'halign')) {
			$xml->addAttribute('halign', $halign);
		}
		if (!property_exists($xml, 'valign')) {
			$xml->addAttribute('valign', $valign);
		}
	}

	/**
	 * Add translate attribute to an xml element
	 *
	 * @param \SimpleXMLElement $xml        	
	 * @param bool $translate        	
	 */
	public static function addTranslate(\SimpleXMLElement $xml, $translate = true) {
		if (!property_exists($xml, 'translate')) {
			$xml->addAttribute('translate', ($translate ? 1 : 0));
		}
	}
}

?>
