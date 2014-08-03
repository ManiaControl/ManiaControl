<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element for going to a link
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Go_To extends Element {
	/*
	 * Protected properties
	 */
	protected $tagName = 'goto';
	protected $link = null;

	/**
	 * Create a new Go_To object
	 *
	 * @param string $link (optional) Goto link
	 * @return static
	 */
	public static function create($link = null) {
		return new static($link);
	}

	/**
	 * Construct a new Go_To object
	 *
	 * @param string $link (optional) Goto link
	 */
	public function __construct($link = null) {
		if (!is_null($link)) {
			$this->setLink($link);
		}
	}

	/**
	 * Set link
	 *
	 * @param string $link Goto link
	 * @return static
	 */
	public function setLink($link) {
		$this->link = (string)$link;
		return $this;
	}

	/**
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement  = parent::render($domDocument);
		$linkElement = $domDocument->createElement('link', $this->link);
		$xmlElement->appendChild($linkElement);
		return $xmlElement;
	}
}
