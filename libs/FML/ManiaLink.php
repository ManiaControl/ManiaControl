<?php

namespace FML;

use FML\Elements\Dico;
use FML\Script\Script;
use FML\Stylesheet\Stylesheet;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;

/**
 * Class representing a ManiaLink
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaLink {
	/*
	 * Constants
	 */
	const BACKGROUND_0        = '0';
	const BACKGROUND_1        = '1';
	const BACKGROUND_STARS    = 'stars';
	const BACKGROUND_STATIONS = 'stations';
	const BACKGROUND_TITLE    = 'title';

	/*
	 * Protected properties
	 */
	protected $encoding = 'utf-8';
	protected $tagName = 'manialink';
	protected $maniaLinkId = null;
	protected $version = 1;
	protected $background = null;
	protected $navigable3d = 1;
	protected $name = null;
	protected $timeout = 0;
	/** @var Renderable[] $children */
	protected $children = array();
	/** @var Dico $dico */
	protected $dico = null;
	/** @var Stylesheet $stylesheet */
	protected $stylesheet = null;
	/** @var Script $script */
	protected $script = null;

	/**
	 * Create a new ManiaLink object
	 *
	 * @param string $maniaLinkId   (optional) ManiaLink id
	 * @param string $maniaLinkName (optional) ManiaLink Name
	 * @return static
	 */
	public static function create($maniaLinkId = null, $maniaLinkName = null) {
		return new static($maniaLinkId, $maniaLinkName);
	}

	/**
	 * Construct a new ManiaLink object
	 *
	 * @param string $maniaLinkId   (optional) ManiaLink id
	 * @param string $maniaLinkName (optional) ManiaLink Name
	 */
	public function __construct($maniaLinkId = null, $maniaLinkName = null) {
		if ($maniaLinkId !== null) {
			$this->setId($maniaLinkId);
		}
		if ($maniaLinkName !== false) {
			if ($maniaLinkName) {
				$this->setName($maniaLinkName);
			} else {
				$this->setName($maniaLinkId);
			}
		}
	}

	/**
	 * Set XML encoding
	 *
	 * @param string $encoding XML encoding
	 * @return static
	 */
	public function setXmlEncoding($encoding) {
		$this->encoding = (string)$encoding;
		return $this;
	}

	/**
	 * Set ManiaLink id
	 *
	 * @param string $maniaLinkId ManiaLink id
	 * @return static
	 */
	public function setId($maniaLinkId) {
		$this->maniaLinkId = (string)$maniaLinkId;
		return $this;
	}

	/**
	 * Get ManiaLink id
	 *
	 * @return string
	 */
	public function getId() {
		return $this->maniaLinkId;
	}

	/**
	 * Set background
	 *
	 * @param string $background Background value
	 * @return static
	 */
	public function setBackground($background) {
		$this->background = (string)$background;
		return $this;
	}

	/**
	 * Set navigable3d
	 *
	 * @param bool $navigable3d Whether the manialink should be 3d navigable
	 * @return static
	 */
	public function setNavigable3d($navigable3d) {
		$this->navigable3d = ($navigable3d ? 1 : 0);
		return $this;
	}

	/**
	 * Set the ManiaLink Name
	 *
	 * @param string $name
	 * @return static
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set timeout
	 *
	 * @param int $timeout Timeout duration
	 * @return static
	 */
	public function setTimeout($timeout) {
		$this->timeout = (int)$timeout;
		return $this;
	}

	/**
	 * Add an element to the ManiaLink
	 *
	 * @param Renderable $child Child element to add
	 * @return static
	 */
	public function add(Renderable $child) {
		if (!in_array($child, $this->children, true)) {
			array_push($this->children, $child);
		}
		return $this;
	}

	/**
	 * Remove all elements from the ManiaLink
	 *
	 * @return static
	 */
	public function removeChildren() {
		$this->children = array();
		return $this;
	}

	/**
	 * Set the Dictionary of the ManiaLink
	 *
	 * @param Dico $dico Dictionary for the ManiaLink
	 * @return static
	 */
	public function setDico(Dico $dico) {
		$this->dico = $dico;
		return $this;
	}

	/**
	 * Get the Dictionary of the ManiaLink
	 *
	 * @param bool $createIfEmpty (optional) Whether the Dico object should be created if it's not set
	 * @return \FML\Elements\Dico
	 */
	public function getDico($createIfEmpty = true) {
		if (!$this->dico && $createIfEmpty) {
			$this->setDico(new Dico());
		}
		return $this->dico;
	}

	/**
	 * Set the Stylesheet of the ManiaLink
	 *
	 * @param Stylesheet $stylesheet Stylesheet for the ManiaLink
	 * @return static
	 */
	public function setStylesheet(Stylesheet $stylesheet) {
		$this->stylesheet = $stylesheet;
		return $this;
	}

	/**
	 * Get the Stylesheet of the ManiaLink
	 *
	 * @param bool $createIfEmpty (optional) Whether the Stylesheet object should be created if it's not set
	 * @return \FML\Stylesheet\Stylesheet
	 */
	public function getStylesheet($createIfEmpty = true) {
		if (!$this->stylesheet && $createIfEmpty) {
			$this->setStylesheet(new Stylesheet());
		}
		return $this->stylesheet;
	}

	/**
	 * Set the Script of the ManiaLink
	 *
	 * @param Script $script Script for the ManiaLink
	 * @return static
	 */
	public function setScript(Script $script) {
		$this->script = $script;
		return $this;
	}

	/**
	 * Get the current Script of the ManiaLink
	 *
	 * @param bool $createIfEmpty (optional) Whether the Script object should be created if it's not set
	 * @return \FML\Script\Script
	 */
	public function getScript($createIfEmpty = true) {
		if (!$this->script && $createIfEmpty) {
			$this->setScript(new Script());
		}
		return $this->script;
	}

	/**
	 * Render the XML document
	 *
	 * @param bool         $echo        (optional) If the XML text should be echoed and the Content-Type header should be set
	 * @param \DOMDocument $domDocument (optional) DOMDocument for which the XML element should be created
	 * @return \DOMDocument
	 */
	public function render($echo = false, $domDocument = null) {
		$isChild = (bool)$domDocument;
		if (!$isChild) {
			$domDocument                = new \DOMDocument('1.0', $this->encoding);
			$domDocument->xmlStandalone = true;
		}
		$maniaLink = $domDocument->createElement($this->tagName);
		if (!$isChild) {
			$domDocument->appendChild($maniaLink);
		}
		if (strlen($this->maniaLinkId) > 0) {
			$maniaLink->setAttribute('id', $this->maniaLinkId);
		}
		if ($this->version) {
			$maniaLink->setAttribute('version', $this->version);
		}
		if (strlen($this->background) > 0) {
			$maniaLink->setAttribute('background', $this->background);
		}
		if (!$this->navigable3d) {
			$maniaLink->setAttribute('navigable3d', $this->navigable3d);
		}
		if ($this->name) {
			$maniaLink->setAttribute('name', $this->name);
		}
		if ($this->timeout) {
			$timeoutXml = $domDocument->createElement('timeout', $this->timeout);
			$maniaLink->appendChild($timeoutXml);
		}
		if ($this->dico) {
			$dicoXml = $this->dico->render($domDocument);
			$maniaLink->appendChild($dicoXml);
		}
		$scriptFeatures = array();
		foreach ($this->children as $child) {
			$childXml = $child->render($domDocument, $this->getScript());
			$maniaLink->appendChild($childXml);
			if ($child instanceof ScriptFeatureable) {
				$scriptFeatures = array_merge($scriptFeatures, $child->getScriptFeatures());
			}
		}
		if ($scriptFeatures) {
			$this->getScript()->loadFeatures($scriptFeatures);
		}
		if ($this->stylesheet) {
			$stylesheetXml = $this->stylesheet->render($domDocument);
			$maniaLink->appendChild($stylesheetXml);
		}
		if ($this->script) {
			if ($this->script->needsRendering()) {
				$scriptXml = $this->script->render($domDocument);
				$maniaLink->appendChild($scriptXml);
			}
			$this->script->resetGenericScriptLabels();
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
	 * Get string representation
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->render()->saveXML();
	}
}
