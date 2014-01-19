<?php

namespace FML;

use FML\Types\Renderable;
use FML\Script\Script;
use FML\Elements\Dico;
use FML\Stylesheet\Stylesheet;

/**
 * Class representing a ManiaLink
 *
 * @author steeffeen
 */
class ManiaLink {
	/**
	 * Constants
	 */
	const BACKGROUND_0 = '0';
	const BACKGROUND_STARS = 'stars';
	const BACKGROUND_STATIONS = 'stations';
	const BACKGROUND_TITLE = 'title';
	
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
	protected $dico = null;
	protected $stylesheet = null;
	protected $script = null;

	/**
	 * Create a new ManiaLink Object
	 *
	 * @param string $id (optional) Manialink Id
	 * @return \FML\ManiaLink
	 */
	public static function create($id = null) {
		$maniaLink = new ManiaLink($id);
		return $maniaLink;
	}

	/**
	 * Construct a new ManiaLink Object
	 *
	 * @param string $id (optional) Manialink Id
	 */
	public function __construct($id = null) {
		if ($id !== null) {
			$this->setId($id);
		}
	}

	/**
	 * Set XML Encoding
	 *
	 * @param string $encoding XML Encoding
	 * @return \FML\ManiaLink
	 */
	public function setXmlEncoding($encoding) {
		$this->encoding = (string) $encoding;
		return $this;
	}

	/**
	 * Set Manialink Id
	 *
	 * @param string $id Manialink Id
	 * @return \FML\ManiaLink
	 */
	public function setId($id) {
		$this->id = (string) $id;
		return $this;
	}

	/**
	 * Set Background
	 *
	 * @param string $background Background Value
	 * @return \FML\ManiaLink
	 */
	public function setBackground($background) {
		$this->background = (string) $background;
		return $this;
	}

	/**
	 * Set Navigable3d
	 *
	 * @param bool $navigable3d Whether the manialink should be 3d navigable
	 * @return \FML\ManiaLink
	 */
	public function setNavigable3d($navigable3d) {
		$this->navigable3d = ($navigable3d ? 1 : 0);
		return $this;
	}

	/**
	 * Set Timeout
	 *
	 * @param int $timeout Timeout Duration
	 * @return \FML\ManiaLink
	 */
	public function setTimeout($timeout) {
		$this->timeout = (int) $timeout;
		return $this;
	}

	/**
	 * Add an Element to the ManiaLink
	 *
	 * @return \FML\ManiaLink
	 */
	public function add(Renderable $child) {
		if (!in_array($child, $this->children, true)) {
			array_push($this->children, $child);
		}
		return $this;
	}

	/**
	 * Remove all Elements from the ManiaLinks
	 *
	 * @return \FML\ManiaLink
	 */
	public function removeChildren() {
		$this->children = array();
		return $this;
	}

	/**
	 * Set the Dictionary of the ManiaLink
	 *
	 * @param Dico $dico The Dictionary to use
	 * @return \FML\ManiaLink
	 */
	public function setDico(Dico $dico) {
		$this->dico = $dico;
		return $this;
	}

	/**
	 * Get the current Dictionary of the ManiaLink
	 *
	 * @param bool $createIfEmpty (optional) Whether the Dico Object should be created if it's not set yet
	 * @return \FML\Elements\Dico
	 */
	public function getDico($createIfEmpty = true) {
		if (!$this->dico && $createIfEmpty) {
			$this->dico = new Dico();
		}
		return $this->dico;
	}

	/**
	 * Set the Stylesheet of the ManiaLink
	 *
	 * @param Stylesheet $stylesheet Stylesheet Object
	 * @return \FML\ManiaLink
	 */
	public function setStylesheet(Stylesheet $stylesheet) {
		$this->stylesheet = $stylesheet;
		return $this;
	}

	/**
	 * Get the Stylesheet of the ManiaLink
	 *
	 * @param bool $createIfEmpty (optional) Whether the Script Object should be created if it's not set yet
	 * @return \FML\Stylesheet\Stylesheet
	 */
	public function getStylesheet($createIfEmpty = true) {
		if (!$this->stylesheet && $createIfEmpty) {
			$this->stylesheet = new Stylesheet();
		}
		return $this->stylesheet;
	}

	/**
	 * Set the Script of the ManiaLink
	 *
	 * @param Script $script The Script for the ManiaLink
	 * @return \FML\ManiaLink
	 */
	public function setScript(Script $script) {
		$this->script = $script;
		return $this;
	}

	/**
	 * Get the current Script of the ManiaLink
	 *
	 * @param bool $createIfEmpty (optional) Whether the Script Object should be created if it's not set yet
	 * @return \FML\Script\Script
	 */
	public function getScript($createIfEmpty = true) {
		if (!$this->script && $createIfEmpty) {
			$this->script = new Script();
		}
		return $this->script;
	}

	/**
	 * Render the XML Document
	 *
	 * @param bool (optional) $echo If the XML Text should be echoed and the Content-Type Header should be set
	 * @param \DOMDocument $domDocument (optional) DOMDocument for which the XML Element should be created
	 * @return \DOMDocument
	 */
	public function render($echo = false, $domDocument = null) {
		$isChild = (bool) $domDocument;
		if (!$isChild) {
			$domDocument = new \DOMDocument('1.0', $this->encoding);
			$domDocument->xmlStandalone = true;
		}
		$maniaLink = $domDocument->createElement($this->tagName);
		if (!$isChild) {
			$domDocument->appendChild($maniaLink);
		}
		if ($this->id) {
			$maniaLink->setAttribute('id', $this->id);
		}
		if ($this->version) {
			$maniaLink->setAttribute('version', $this->version);
		}
		if ($this->background) {
			$maniaLink->setAttribute('background', $this->background);
		}
		if (!$this->navigable3d) {
			$maniaLink->setAttribute('navigable3d', $this->navigable3d);
		}
		if ($this->timeout) {
			$timeoutXml = $domDocument->createElement('timeout', $this->timeout);
			$maniaLink->appendChild($timeoutXml);
		}
		foreach ($this->children as $child) {
			$childXml = $child->render($domDocument);
			$maniaLink->appendChild($childXml);
		}
		if ($this->dico) {
			$dicoXml = $this->dico->render($domDocument);
			$maniaLink->appendChild($dicoXml);
		}
		if ($this->stylesheet) {
			$stylesheetXml = $this->stylesheet->render($domDocument);
			$maniaLink->appendChild($stylesheetXml);
		}
		if ($this->script) {
			$scriptXml = $this->script->render($domDocument);
			$maniaLink->appendChild($scriptXml);
		}
		if ($isChild) {
			return $maniaLink;
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
