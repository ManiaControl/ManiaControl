<?php

namespace FML\Controls;

use FML\Elements\Format;
use FML\Types\Container;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;

/**
 * Frame Control
 * (CMlFrame)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Frame extends Control implements Container {
	/*
	 * Protected Properties
	 */
	protected $children = array();
	/** @var Format $format */
	protected $format = null;

	/**
	 * Construct a new Frame Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'frame';
	}

	/**
	 * Create a new Frame Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Frame
	 */
	public static function create($id = null) {
		$frame = new Frame($id);
		return $frame;
	}

	/**
	 * @see \FML\Controls\Control::getManiaScriptClass()
	 */
	public function getManiaScriptClass() {
		return 'CMlFrame';
	}

	/**
	 * @see \FML\Types\Container::add()
	 */
	public function add(Renderable $child) {
		if (!in_array($child, $this->children, true)) {
			array_push($this->children, $child);
		}
		return $this;
	}

	/**
	 * @see \FML\Types\Container::removeChildren()
	 */
	public function removeChildren() {
		$this->children = array();
		return $this;
	}

	/**
	 * @see \FML\Types\Container::getFormat()
	 */
	public function getFormat($createIfEmpty = true) {
		if (!$this->format && $createIfEmpty) {
			$this->setFormat(new Format());
		}
		return $this->format;
	}

	/**
	 * @see \FML\Types\Container::setFormat()
	 */
	public function setFormat(Format $format) {
		$this->format = $format;
		return $this;
	}

	/**
	 * @see \FML\Controls\Control::getScriptFeatures()
	 */
	public function getScriptFeatures() {
		$scriptFeatures = $this->scriptFeatures;
		foreach ($this->children as $child) {
			if ($child instanceof ScriptFeatureable) {
				$scriptFeatures = array_merge($scriptFeatures, $child->getScriptFeatures());
			}
		}
		return $scriptFeatures;
	}

	/**
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->format) {
			$formatXml = $this->format->render($domDocument);
			$xmlElement->appendChild($formatXml);
		}
		foreach ($this->children as $child) {
			/** @var Renderable $child */
			$childXmlElement = $child->render($domDocument);
			$xmlElement->appendChild($childXmlElement);
		}
		return $xmlElement;
	}
}
