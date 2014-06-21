<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element for playing a map
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayMap implements Element {
	/*
	 * Protected properties
	 */
	protected $tagName = 'play_map';
	protected $name = null;
	protected $url = null;

	/**
	 * Create a new PlayMap object
	 *
	 * @param string $name (optional) Map name
	 * @param string $url  (optional) Map url
	 * @return \FML\ManiaCode\PlayMap|static
	 */
	public static function create($name = null, $url = null) {
		return new static($name, $url);
	}

	/**
	 * Construct a new PlayMap object
	 *
	 * @param string $name (optional) Map name
	 * @param string $url  (optional) Map url
	 */
	public function __construct($name = null, $url = null) {
		if (!is_null($name)) {
			$this->setName($name);
		}
		if (!is_null($url)) {
			$this->setUrl($url);
		}
	}

	/**
	 * Set the name of the map
	 *
	 * @param string $name Map name
	 * @return \FML\ManiaCode\PlayMap|static
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the url of the map
	 *
	 * @param string $url Map url
	 * @return \FML\ManiaCode\PlayMap|static
	 */
	public function setUrl($url) {
		$this->url = (string)$url;
		return $this;
	}

	/**
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement  = $domDocument->createElement($this->tagName);
		$nameElement = $domDocument->createElement('name', $this->name);
		$xmlElement->appendChild($nameElement);
		$urlElement = $domDocument->createElement('url', $this->url);
		$xmlElement->appendChild($urlElement);
		return $xmlElement;
	}
}
