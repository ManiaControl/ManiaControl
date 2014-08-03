<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a script
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class InstallScript extends Element {
	/*
	 * Protected properties
	 */
	protected $tagName = 'install_script';
	protected $name = null;
	protected $file = null;
	protected $url = null;

	/**
	 * Create a new InstallScript object
	 *
	 * @param string $name (optional) Script name
	 * @param string $file (optional) Script file
	 * @param string $url  (optional) Script url
	 * @return static
	 */
	public static function create($name = null, $file = null, $url = null) {
		return new static($name, $file, $url);
	}

	/**
	 * Construct a new InstallScript object
	 *
	 * @param string $name (optional) Script name
	 * @param string $file (optional) Script file
	 * @param string $url  (optional) Script url
	 */
	public function __construct($name = null, $file = null, $url = null) {
		if (!is_null($name)) {
			$this->setName($name);
		}
		if (!is_null($file)) {
			$this->setFile($file);
		}
		if (!is_null($url)) {
			$this->setUrl($url);
		}
	}

	/**
	 * Set the name of the script
	 *
	 * @param string $name Script name
	 * @return static
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the file of the script
	 *
	 * @param string $file Script file
	 * @return static
	 */
	public function setFile($file) {
		$this->file = (string)$file;
		return $this;
	}

	/**
	 * Set the url of the script
	 *
	 * @param string $url Script url
	 * @return static
	 */
	public function setUrl($url) {
		$this->url = (string)$url;
		return $this;
	}

	/**
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement  = parent::render($domDocument);
		$nameElement = $domDocument->createElement('name', $this->name);
		$xmlElement->appendChild($nameElement);
		$fileElement = $domDocument->createElement('file', $this->file);
		$xmlElement->appendChild($fileElement);
		$urlElement = $domDocument->createElement('url', $this->url);
		$xmlElement->appendChild($urlElement);
		return $xmlElement;
	}
}
