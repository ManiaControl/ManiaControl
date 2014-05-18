<?php

namespace FML\Types;

/**
 * Interface for Elements with Style Attribute
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Styleable {

	/**
	 * Set Style
	 *
	 * @param string $style Style Name
	 * @return \FML\Types\Styleable
	 */
	public function setStyle($style);
}
