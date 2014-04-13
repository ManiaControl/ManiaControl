<?php

namespace FML;

/**
 * Class holding several ManiaLinks at once
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaLinks {
	/*
	 * Protected Properties
	 */
	protected $encoding = 'utf-8';
	protected $tagName = 'manialinks';
	protected $children = array();
	protected $customUI = null;

	/**
	 * Create a new ManiaLinks Object
	 *
	 * @return \FML\ManiaLinks
	 */
	public static function create() {
		$maniaLinks = new ManiaLinks();
		return $maniaLinks;
	}

	/**
	 * Construct a new ManiaLinks Object
	 */
	public function __construct() {
	}

	/**
	 * Set XML Encoding
	 *
	 * @param string $encoding XML Encoding
	 * @return \FML\ManiaLinks
	 */
	public function setXmlEncoding($encoding) {
		$this->encoding = (string) $encoding;
		return $this;
	}

	/**
	 * Add a Child ManiaLink
	 *
	 * @param ManiaLink $child Child ManiaLink
	 * @return \FML\ManiaLinks
	 */
	public function add(ManiaLink $child) {
		if (!in_array($child, $this->children, true)) {
			array_push($this->children, $child);
		}
		return $this;
	}

	/**
	 * Remove all Child ManiaLinks
	 *
	 * @return \FML\ManiaLinks
	 */
	public function removeChildren() {
		$this->children = array();
		return $this;
	}

	/**
	 * Set the CustomUI
	 *
	 * @param CustomUI $customUI The CustomUI Object
	 * @return \FML\ManiaLinks
	 */
	public function setCustomUI(CustomUI $customUI) {
		$this->customUI = $customUI;
		return $this;
	}

	/**
	 * Get the current CustomUI
	 *
	 * @param bool $createIfEmpty (optional) Whether the CustomUI Object should be created if it's not set yet
	 * @return \FML\CustomUI
	 */
	public function getCustomUI($createIfEmpty = true) {
		if (!$this->customUI && $createIfEmpty) {
			$this->setCustomUI(new CustomUI());
		}
		return $this->customUI;
	}

	/**
	 * Render the XML Document
	 *
	 * @param bool (optional) $echo Whether the XML Text should be echoed and the Content-Type Header should be set
	 * @return \DOMDocument
	 */
	public function render($echo = false) {
		$domDocument = new \DOMDocument('1.0', $this->encoding);
		$domDocument->xmlStandalone = true;
		$maniaLinks = $domDocument->createElement($this->tagName);
		$domDocument->appendChild($maniaLinks);
		foreach ($this->children as $child) {
			$childXml = $child->render(false, $domDocument);
			$maniaLinks->appendChild($childXml);
		}
		if ($this->customUI) {
			$customUIXml = $this->customUI->render($domDocument);
			$maniaLinks->appendChild($customUIXml);
		}
		if ($echo) {
			header('Content-Type: application/xml; charset=utf-8;');
			echo $domDocument->saveXML();
		}
		return $domDocument;
	}

	/**
	 * Get String Representation
	 *
	 * @return string
	 */
	public function __toString() {
		$domDocument = $this->render();
		$xmlText = $domDocument->saveXML();
		return $xmlText;
	}
}
