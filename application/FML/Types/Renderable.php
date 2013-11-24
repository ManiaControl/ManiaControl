<?php

namespace FML\Types;

/**
 * Interface for renderable elements
 *
 * @author steeffeen
 */
interface Renderable {

	/**
	 * Render the xml element
	 *
	 * @param \DOMDocument $domDocument        	
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument);
}

?>
