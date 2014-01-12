<?php

namespace FML\Controls;

use FML\Types\Container;
use FML\Types\Renderable;

/**
 * Frame Element
 * (CMlFrame)
 *
 * @author steeffeen
 */
class Frame extends Control implements Container {
	/**
	 * Protected Properties
	 */
	protected $children = array();

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
	public function add(Renderable $child) {
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
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		foreach ($this->children as $child) {
			$childXmlElement = $child->render($domDocument);
			$xmlElement->appendChild($childXmlElement);
		}
		return $xmlElement;
	}
}
