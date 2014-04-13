<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element playing a Map
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayMap implements Element {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'play_map';
	protected $name = '';
	protected $url = '';

	/**
	 * Create a new PlayMap Element
	 *
	 * @param string $name (optional) Map Name
	 * @param string $url (optional) Map Url
	 * @return \FML\ManiaCode\PlayMap
	 */
	public static function create($name = null, $url = null) {
		$playMap = new PlayMap($name, $url);
		return $playMap;
	}

	/**
	 * Construct a new PlayMap Element
	 *
	 * @param string $name (optional) Map Name
	 * @param string $url (optional) Map Url
	 */
	public function __construct($name = null, $url = null) {
		if ($name !== null) {
			$this->setName($name);
		}
		if ($url !== null) {
			$this->setUrl($url);
		}
	}

	/**
	 * Set the Name of the Map
	 *
	 * @param string $name Map Name
	 * @return \FML\ManiaCode\PlayMap
	 */
	public function setName($name) {
		$this->name = (string) $name;
		return $this;
	}

	/**
	 * Set the Url of the Map
	 *
	 * @param string $url Map Url
	 * @return \FML\ManiaCode\PlayMap
	 */
	public function setUrl($url) {
		$this->url = (string) $url;
		return $this;
	}

	/**
	 *
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		$nameElement = $domDocument->createElement('name', $this->name);
		$xmlElement->appendChild($nameElement);
		$urlElement = $domDocument->createElement('url', $this->url);
		$xmlElement->appendChild($urlElement);
		return $xmlElement;
	}
}
