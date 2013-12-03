<?php

namespace FML;

use FML\Types\Container;
use FML\Types\Renderable;
use FML\Script\Script;

/**
 * Class representing a manialink
 *
 * @author steeffeen
 */
class ManiaLink implements Container {
	/**
	 * Protected properties
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
	 * Construct a new manialink
	 */
	public function __construct($id = null) {
		if ($id !== null) {
			$this->setId($id);
		}
	}

	/**
	 * Set xml encoding
	 *
	 * @param string $encoding        	
	 * @return \FML\ManiaLink
	 */
	public function setXmlEncoding($encoding) {
		$this->encoding = $encoding;
		return $this;
	}

	/**
	 * Set id
	 *
	 * @param string $id        	
	 * @return \FML\ManiaLink
	 */
	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	/**
	 * Set background
	 *
	 * @param string $background        	
	 * @return \FML\ManiaLink
	 */
	public function setBackground($background) {
		$this->background = $background;
		return $this;
	}

	/**
	 * Set navigable3d
	 *
	 * @param bool $navigable3d        	
	 * @return \FML\ManiaLink
	 */
	public function setNavigable3d($navigable3d) {
		$this->navigable3d = ($navigable3d ? 1 : 0);
		return $this;
	}

	/**
	 * Set timeout
	 *
	 * @param int $timeout        	
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
	 * Set the script object of the manalink
	 *
	 * @param Script $script        	
	 * @return \FML\ManiaLink
	 */
	public function setScript(Script $script) {
		$this->script = $script;
		return $this;
	}

	/**
	 * Render the xml document
	 *
	 * @param bool $echo
	 *        	If the xml should be echoed and the content-type header should be set
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
}

?>
