<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a Map
 *
 * @author steeffeen
 */
class InstallMap implements Element {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'install_map';
	protected $name = '';
	protected $url = '';

	/**
	 * Create a new InstallMap Element
	 *
	 * @param string $name (optional) Map Name
	 * @param string $url (optional) Map Url
	 * @return \FML\ManiaCode\InstallMap
	 */
	public static function create($name = null, $url = null) {
		$installMap = new InstallMap($name, $url);
		return $installMap;
	}

	/**
	 * Construct a new InstallMap Element
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
	 * @return \FML\ManiaCode\InstallMap
	 */
	public function setName($name) {
		$this->name = (string) $name;
		return $this;
	}

	/**
	 * Set the Url of the Map
	 *
	 * @param string $url Map Url
	 * @return \FML\ManiaCode\InstallMap
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
