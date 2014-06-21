<?php

namespace FML;

/**
 * Class holding several ManiaLinks at once
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaLinks {
	/*
	 * Protected Properties
	 */
	protected $encoding = 'utf-8';
	protected $tagName = 'manialinks';
	/** @var ManiaLink[] $children */
	protected $children = array();
	/** @var CustomUI $customUI */
	protected $customUI = null;

	/**
	 * Create a new ManiaLinks object
	 *
	 * @return \FML\ManiaLinks|static
	 */
	public static function create() {
		return new static();
	}

	/**
	 * Set XML encoding
	 *
	 * @param string $encoding XML encoding
	 * @return \FML\ManiaLinks|static
	 */
	public function setXmlEncoding($encoding) {
		$this->encoding = (string)$encoding;
		return $this;
	}

	/**
	 * Add a child ManiaLink
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
	 * Remove all child ManiaLinks
	 *
	 * @return \FML\ManiaLinks|static
	 */
	public function removeChildren() {
		$this->children = array();
		return $this;
	}

	/**
	 * Set the CustomUI
	 *
	 * @param CustomUI $customUI CustomUI object
	 * @return \FML\ManiaLinks|static
	 */
	public function setCustomUI(CustomUI $customUI) {
		$this->customUI = $customUI;
		return $this;
	}

	/**
	 * Get the CustomUI
	 *
	 * @param bool $createIfEmpty (optional) Whether the CustomUI object should be created if it's not set
	 * @return \FML\CustomUI
	 */
	public function getCustomUI($createIfEmpty = true) {
		if (!$this->customUI && $createIfEmpty) {
			$this->setCustomUI(new CustomUI());
		}
		return $this->customUI;
	}

	/**
	 * Render the XML document
	 *
	 * @param bool (optional) $echo Whether the XML text should be echoed and the Content-Type header should be set
	 * @return \DOMDocument
	 */
	public function render($echo = false) {
		$domDocument                = new \DOMDocument('1.0', $this->encoding);
		$domDocument->xmlStandalone = true;
		$maniaLinks                 = $domDocument->createElement($this->tagName);
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
	 * Get string representation
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->render()->saveXML();
	}
}
