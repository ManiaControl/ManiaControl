<?php

namespace FML\Types;

/**
 * Interface for Elements with background color attribute
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface BgColorable {

	/**
	 * Set background color
	 *
	 * @param string $bgColor Background color
	 * @return static
	 */
	public function setBgColor($bgColor);
}
