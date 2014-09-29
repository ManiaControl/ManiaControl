<?php

namespace FML\Elements;

use FML\Types\Renderable;

/**
 * Class representing a ManiaLink script tag with a simple script text
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class SimpleScript implements Renderable {
	/*
	 * Protected properties
	 */
	protected $tagName = 'script';
	protected $text = null;

	/**
	 * Create a new SimpleScript object
	 *
	 * @param string $text (optional) Script text
	 * @return static
	 */
	public static function create($text = null) {
		return new static($text);
	}

	/**
	 * Construct a new SimpleScript object
	 *
	 * @param string $text (optional) Script text
	 */
	public function __construct($text = null) {
		if ($text !== null) {
			$this->setText($text);
		}
	}

	/**
	 * Set script text
	 *
	 * @param string $text Complete script text
	 * @return static
	 */
	public function setText($text) {
		$this->text = (string)$text;
		return $this;
	}

	/**
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		if ($this->text) {
			$scriptComment = $domDocument->createComment($this->text);
			$xmlElement->appendChild($scriptComment);
		}
		return $xmlElement;
	}
}
