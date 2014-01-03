<?php

namespace FML;

use FML\Types\Container;
use FML\Types\Renderable;
use FML\Script\Script;

/**
 * Class representing a Manialink
 *
 * @author steeffeen
 */
class ManiaLink implements Container {
	/**
	 * Protected Properties
	 */
	protected $encoding = 'utf-8';
	protected $tagName = 'manialink';
	protected $id = '';
	protected $version = 1;
	protected $background = '';
	protected $navigable3d = 0;
	protected $timeout = 0;
	protected $children = array();
	protected $script = null;

	/**
	 * Construct a new Manialink
	 *
	 * @param string $id
	 *        	Manialink Id
	 */
	public function __construct($id = null) {
		if ($id !== null) {
			$this->setId($id);
		}
	}

	/**
	 * Set XML Encoding
	 *
	 * @param string $encoding
	 *        	XML Encoding
	 * @return \FML\ManiaLink
	 */
	public function setXmlEncoding($encoding) {
		$this->encoding = $encoding;
		return $this;
	}

	/**
	 * Set Manialink Id
	 *
	 * @param string $id
	 *        	Manialink Id
	 * @return \FML\ManiaLink
	 */
	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	/**
	 * Set Background
	 *
	 * @param string $background
	 *        	Background Value
	 * @return \FML\ManiaLink
	 */
	public function setBackground($background) {
		$this->background = $background;
		return $this;
	}

	/**
	 * Set Navigable3d
	 *
	 * @param bool $navigable3d
	 *        	If the manialink is 3d navigable
	 * @return \FML\ManiaLink
	 */
	public function setNavigable3d($navigable3d) {
		$this->navigable3d = ($navigable3d ? 1 : 0);
		return $this;
	}

	/**
	 * Set Timeout
	 *
	 * @param int $timeout
	 *        	Timeout Duration
	 * @return \FML\ManiaLink
	 */
	public function setTimeout($timeout) {
		$this->timeout = $timeout;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Container::add()
	 * @return \FML\ManiaLink
	 */
	public function add(Renderable $child) {
		array_push($this->children, $child);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Container::removeChildren()
	 * @return \FML\ManiaLink
	 */
	public function removeChildren() {
		$this->children = array();
		return $this;
	}

	/**
	 * Set the script object of the Manialink
	 *
	 * @param Script $script        	
	 * @return \FML\ManiaLink
	 */
	public function setScript(Script $script) {
		$this->script = $script;
		return $this;
	}

	/**
	 * Render the XML Document
	 *
	 * @param bool $echo
	 *        	If the xml should be echoed and the content-type header should be set
	 * @param \DOMDocument $domDocument        	
	 * @return \DOMDocument
	 */
	public function render($echo = false, $domDocument = null) {
		$isChild = false;
		if ($domDocument) {
			$isChild = true;
		}
		if (!$isChild) {
			$domDocument = new \DOMDocument('1.0', $this->encoding);
		}
		$manialink = $domDocument->createElement($this->tagName);
		if (!$isChild) {
			$domDocument->appendChild($manialink);
		}
		if ($this->id) {
			$manialink->setAttribute('id', $this->id);
		}
		if ($this->version) {
			$manialink->setAttribute('version', $this->version);
		}
		if ($this->background) {
			$manialink->setAttribute('background', $this->background);
		}
		if ($this->navigable3d) {
			$manialink->setAttribute('navigable3d', $this->navigable3d);
		}
		if ($this->timeout) {
			$timeoutXml = $domDocument->createElement('timeout', $this->timeout);
			$manialink->appendChild($timeoutXml);
		}
		foreach ($this->children as $child) {
			$childXml = $child->render($domDocument);
			$manialink->appendChild($childXml);
		}
		if ($this->script) {
			$scriptXml = $this->script->render($domDocument);
			$manialink->appendChild($scriptXml);
		}
		if ($isChild) {
			return $manialink;
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
