<?php

namespace FML\Elements;

/**
 * Class representing a format
 *
 * @author steeffeen
 */
class Format implements BgColorable, Renderable, Styleable, TextFormatable {
	/**
	 * Protected properties
	 */
	protected $tagName = 'format';

	/**
	 *
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = $domDocument->createElement($this->tagName);
		return $xml;
	}
}

?>
