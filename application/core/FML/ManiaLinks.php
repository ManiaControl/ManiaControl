<?php

namespace FML;

/**
 * Class holding several Manialinks at once
 *
 * @author steeffeen
 */
class ManiaLinks {
	/**
	 * Protected properties
	 */
	protected $encoding = 'utf-8';
	protected $tagName = 'manialinks';
	protected $children = array();

	/**
	 * Set xml encoding
	 *
	 * @param string $encoding        	
	 * @return \FML\ManiaLinks
	 */
	public function setXmlEncoding($encoding) {
		$this->encoding = $encoding;
		return $this;
	}

	/**
	 * Add a child manialink
	 *
	 * @param ManiaLink $child        	
	 * @return \FML\ManiaLinks
	 */
	public function add(ManiaLink $child) {
		array_push($this->children, $child);
		return $this;
	}

	/**
	 * Remove all child manialinks
	 *
	 * @return \FML\ManiaLinks
	 */
	public function removeChildren() {
		$this->children = array();
		return $this;
	}

	/**
	 * Render the xml document
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
