<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a Script
 *
 * @author steeffeen
 */
class InstallScript implements Element {
	/**
	 * Protected Properties
	 */
	protected $tagName = 'install_script';
	protected $name = '';
	protected $file = '';
	protected $url = '';

	/**
	 * Create a new InstallScript Element
	 *
	 * @param string $name (optional) Script Name
	 * @param string $file (optional) Script File
	 * @param string $url (optional) Script Url
	 * @return \FML\ManiaCode\InstallScript
	 */
	public static function create($name = null, $file = null, $url = null) {
		$installScript = new InstallScript($name, $file, $url);
		return $installScript;
	}

	/**
	 * Construct a new InstallScript Element
	 *
	 * @param string $name (optional) Script Name
	 * @param string $file (optional) Script File
	 * @param string $url (optional) Script Url
	 */
	public function __construct($name = null, $file = null, $url = null) {
		if ($name !== null) {
			$this->setName($name);
		}
		if ($file !== null) {
			$this->setFile($file);
		}
		if ($url !== null) {
			$this->setUrl($url);
		}
	}

	/**
	 * Set the Name of the Script
	 *
	 * @param string $name Script Name
	 * @return \FML\ManiaCode\InstallScript
	 */
	public function setName($name) {
		$this->name = (string) $name;
		return $this;
	}

	/**
	 * Set the File of the Script
	 *
	 * @param string $file Script File
	 * @return \FML\ManiaCode\InstallScript
	 */
	public function setFile($file) {
		$this->file = (string) $file;
		return $this;
	}

	/**
	 * Set the Url of the Script
	 *
	 * @param string $url Script Url
	 * @return \FML\ManiaCode\InstallScript
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
		$fileElement = $domDocument->createElement('file', $this->file);
		$xmlElement->appendChild($fileElement);
		$urlElement = $domDocument->createElement('url', $this->url);
		$xmlElement->appendChild($urlElement);
		return $xmlElement;
	}
}
