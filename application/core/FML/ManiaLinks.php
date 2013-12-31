<?php

namespace FML;

/**
 * Class holding several Manialinks at once
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

	/**
	 * Set XML Encoding
	 *
	 * @param string $encoding
	 *        	XML Encoding
	 * @return \FML\ManiaLinks
	 */
	public function setXmlEncoding($encoding) {
		$this->encoding = $encoding;
		return $this;
	}

	/**
	 * Add a Child Manialink
	 *
	 * @param ManiaLink $child
	 *        	Child Manialink
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
	 * Render the XML Document
	 *
	 * @param bool $echo
	 *        	If the xml should be echoed and the content-type header should be set
	 * @return \DOMDocument
	 */
	public function render($echo = false) {
		$domDocument = new \DOMDocument('1.0', $this->encoding);
		$manialinks = $domDocument->createElement($this->tagName);
		$domDocument->appendChild($manialinks);
		foreach ($this->children as $child) {
			$childXml = $child->render(false, $domDocument);
			$manialinks->appendChild($childXml);
		}
		if ($echo) {
			header('Content-Type: application/xml');
			echo $domDocument->saveXML();
		}
		return $domDocument;
	}
}
