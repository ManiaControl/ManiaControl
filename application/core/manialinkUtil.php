<?php

/**
 * Manialink utility class
 *
 * @author Steff
 */
class ManialinkUtil {

	/**
	 * Build new simple xml element
	 *
	 * @param string $name        	
	 * @param string $id        	
	 * @return SimpleXMLElement
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
	 * @param SimpleXMLElement $xml        	
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
	 * @param SimpleXMLElement $xml        	
	 * @param bool $translate        	
	 */
	public static function addTranslate(\SimpleXMLElement $xml, $translate = true) {
		if (!property_exists($xml, 'translate')) {
			$xml->addAttribute('translate', ($translate ? 1 : 0));
		}
	}
}

?>
