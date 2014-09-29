<?php

namespace FML\Types;

/**
 * Interface for Elements with style attribute
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Styleable {

	/**
	 * Set style
	 *
	 * @param string $style Style name
	 * @return static
	 */
	public function setStyle($style);
}
