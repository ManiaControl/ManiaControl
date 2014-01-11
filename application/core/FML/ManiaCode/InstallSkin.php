<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a Skin
 *
 * @author steeffeen
 */
class InstallSkin implements Element {
	/**
	 * Protected Properties
	 */
	protected $tagName = 'install_skin';
	protected $name = '';
	protected $file = '';
	protected $url = '';

	/**
	 * Construct a new InstallSkin Element
	 *
	 * @param string $name (optional) Skin Name
	 * @param string $file (optional) Skin File
	 * @param string $url (optional) Skin Url
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
	 * Set the Name of the Skin
	 *
	 * @param string $name Skin Name
	 * @return \FML\ManiaCode\InstallSkin
	 */
	public function setName($name) {
		$this->name = (string) $name;
		return $this;
	}

	/**
	 * Set the File of the Skin
	 *
	 * @param string $file Skin File
	 * @return \FML\ManiaCode\InstallSkin
	 */
	public function setFile($file) {
		$this->file = (string) $file;
		return $this;
	}

	/**
	 * Set the Url of the Skin
	 *
	 * @param string $url Skin Url
	 * @return \FML\ManiaCode\InstallSkin
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
