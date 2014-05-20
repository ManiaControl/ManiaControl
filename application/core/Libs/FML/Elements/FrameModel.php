<?php

namespace FML\Elements;

use FML\Types\Container;
use FML\Types\Renderable;

/**
 * Class representing a Frame Model
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class FrameModel implements Container, Renderable {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'framemodel';
	protected $id = '';
	protected $children = array();
	/** @var Format $format */
	protected $format = null;

	/**
	 * Set Model Id
	 *
	 * @param string $id Model Id
	 * @return \FML\Elements\FrameModel
	 */
	public function setId($id) {
		$this->id = (string)$id;
		return $this;
	}

	/**
	 * Get Model Id
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Assign an Id if necessary
	 *
	 * @return string
	 */
	public function checkId() {
		if (!$this->id) {
			$this->id = uniqid();
		}
		return $this;
	}

	/**
	 * @see \FML\Types\Container::add()
	 */
	public function add(Renderable $childElement) {
		if (!in_array($childElement, $this->children, true)) {
			array_push($this->children, $childElement);
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
	 * @see \FML\Types\Container::setFormat()
	 */
	public function setFormat(Format $format) {
		$this->format = $format;
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
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		$this->checkId();
		$xmlElement->setAttribute('id', $this->getId());
		if ($this->format) {
			$formatXml = $this->format->render($domDocument);
			$xmlElement->appendChild($formatXml);
		}
		foreach ($this->children as $child) {
			/** @var Renderable $child */
			$childElement = $child->render($domDocument);
			$xmlElement->appendChild($childElement);
		}
		return $xmlElement;
	}
}
