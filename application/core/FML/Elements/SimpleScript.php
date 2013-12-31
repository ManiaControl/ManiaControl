<?php

namespace FML\Elements;

use FML\Types\Renderable;

/**
 * Class representing a manialink script tag with a simple script text
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
	 * @param string $text        	
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
		$xml = $domDocument->createElement($this->tagName);
		$scriptComment = $domDocument->createComment($this->text);
		$xml->appendChild($scriptComment);
		return $xml;
	}
}
