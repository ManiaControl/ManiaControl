<?php

namespace FML\ManiaCode;

interface Element {

	/**
	 * Render the ManiaCode Element
	 *
	 * @param \DOMDocument $domDocument The DomDocument for which the Element should be rendered
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument);
}
