<?php

namespace FML\ManiaCode;

/**
 * Base ManiaCode Element
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Element {
	/*
	 * Protected properties
	 */
	protected $tagName = 'element';

	/**
	 * Render the ManiaCode Element
	 *
	 * @param \DOMDocument $domDocument The DOMDocument for which the Element should be rendered
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		return $xmlElement;
	}
}
