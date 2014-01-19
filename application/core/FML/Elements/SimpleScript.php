<?php

namespace FML\Elements;

use FML\Types\Renderable;

/**
 * Class representing a ManiaLink Script Tag with a simple Script Text
 *
 * @author steeffeen
 */
class SimpleScript implements Renderable {
	/**
	 * Protected Properties
	 */
	protected $tagName = 'script';
	protected $text = '';

	/**
	 * Create a new SimpleScript Element
	 *
	 * @param string $text (optional) Script Text
	 * @return \FML\Elements\SimpleScript
	 */
	public static function create($text = null) {
		$simpleScript = new SimpleScript($text);
		return $simpleScript;
	}

	/**
	 * Construct a new SimpleScript Element
	 *
	 * @param string $text (optional) Script Text
	 */
	public function __construct($text = null) {
		if ($text !== null) {
			$this->setText($text);
		}
	}

	/**
	 * Set Script Text
	 *
	 * @param string $text The Complete Script Text
	 * @return \FML\Script\Script
	 */
	public function setText($text) {
		$this->text = (string) $text;
		return $this;
	}

	/**
	 *
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
