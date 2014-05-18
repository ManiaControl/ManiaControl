<?php

namespace FML\Types;

/**
 * Interface for renderable Elements
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
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
