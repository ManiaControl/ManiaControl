<?php

namespace FML\Types;

/**
 * Interface for renderable Elements
 *
 * @author steeffeen
 */
interface Renderable {

	/**
	 * Render the XML Element
	 *
	 * @param \DOMDocument $domDocument DomDocument for which the XML Element should be rendered
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument);
}
