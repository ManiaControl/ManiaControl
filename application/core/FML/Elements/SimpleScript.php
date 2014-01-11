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
	 * Set Script Text
	 *
	 * @param string $text The Complete Script Text
	 * @return \FML\Script\Script
	 */
	public function setText($text) {
		$this->text = $text;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		$scriptComment = $domDocument->createComment($this->text);
		$xmlElement->appendChild($scriptComment);
		return $xmlElement;
	}
}
