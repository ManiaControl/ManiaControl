<?php

namespace FML\Controls;

use FML\Types\Container;
use FML\Types\Renderable;
use FML\Elements\Format;
use FML\Elements\FrameModel;

/**
 * Frame Control
 * (CMlFrame)
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Frame extends Control implements Container {
	/*
	 * Protected Properties
	 */
	protected $children = array();
	protected $format = null;

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
	 * Construct a new Frame Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'frame';
	}

	/**
	 *
	 * @see \FML\Types\Container::add()
	 * @return \FML\Controls\Frame
	 */
	public function add(Control $child) {
		if (!in_array($child, $this->children, true)) {
			array_push($this->children, $child);
		}
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Container::removeChildren()
	 * @return \FML\Controls\Frame
	 */
	public function removeChildren() {
		$this->children = array();
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Container::setFormat()
	 * @return \FML\Controls\Frame
	 */
	public function setFormat(Format $format) {
		$this->format = $format;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Container::getFormat()
	 */
	public function getFormat($createIfEmpty = true) {
		if (!$this->format && $createIfEmpty) {
			$this->setFormat(new Format());
		}
		return $this->format;
	}

	/**
	 *
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->format) {
			$formatXml = $this->format->render($domDocument);
			$xmlElement->appendChild($formatXml);
		}
		foreach ($this->children as $child) {
			$childXmlElement = $child->render($domDocument);
			$xmlElement->appendChild($childXmlElement);
		}
		return $xmlElement;
	}
}
