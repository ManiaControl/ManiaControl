<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a Macroblock
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class InstallMacroblock implements Element {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'install_macroblock';
	protected $name = '';
	protected $file = '';
	protected $url = '';

	/**
	 * Create a new InstallMacroblock Element
	 *
	 * @param string $name (optional) Macroblock Name
	 * @param string $url (optional) Macroblock Url
	 * @return \FML\ManiaCode\InstallMacroblock
	 */
	public static function create($name = null, $url = null) {
		$installMacroblock = new InstallMacroblock($name, $url);
		return $installMacroblock;
	}

	/**
	 * Construct a new InstallMacroblock Element
	 *
	 * @param string $name (optional) Macroblock Name
	 * @param string $file (optional) Macroblock File
	 * @param string $url (optional) Macroblock Url
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
	 * Set the Name of the Macroblock
	 *
	 * @param string $name Macroblock Name
	 * @return \FML\ManiaCode\InstallMacroblock
	 */
	public function setName($name) {
		$this->name = (string) $name;
		return $this;
	}

	/**
	 * Set the File of the Macroblock
	 *
	 * @param string $file Macroblock File
	 * @return \FML\ManiaCode\InstallMacroblock
	 */
	public function setFile($file) {
		$this->file = (string) $file;
		return $this;
	}

	/**
	 * Set the Url of the Macroblock
	 *
	 * @param string $url Macroblock Url
	 * @return \FML\ManiaCode\InstallMacroblock
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
