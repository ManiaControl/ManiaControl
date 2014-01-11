<?php

namespace FML;

/**
 * Class holding several ManiaLinks at once
 *
 * @author steeffeen
 */
class ManiaLinks {
	/**
	 * Protected Properties
	 */
	protected $encoding = 'utf-8';
	protected $tagName = 'manialinks';
	protected $children = array();
	protected $customUI = null;

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
	 * Add a Child Manialink
	 *
	 * @param ManiaLink $child Child Manialink
	 * @return \FML\ManiaLinks
	 */
	public function add(ManiaLink $child) {
		if (!in_array($child, $this->children)) {
			array_push($this->children, $child);
		}
		return $this;
	}

	/**
	 * Remove all Child Manialinks
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
	 * Render the XML Document
	 *
	 * @param bool (optional) $echo Whether the XML Text should be echoed and the Content-Type Header should be set
	 * @return \DOMDocument
	 */
	public function render($echo = false) {
		$domDocument = new \DOMDocument('1.0', $this->encoding);
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
			header('Content-Type: application/xml');
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
