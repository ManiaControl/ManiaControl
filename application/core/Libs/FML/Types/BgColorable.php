<?php

namespace FML\Types;

/**
 * Interface for Elements with Background Color Attribute
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface BgColorable {

	/**
	 * Set Background Color
	 *
	 * @param string $bgColor Background Color
	 * @return \FML\Types\BgColorable
	 */
	public function setBgColor($bgColor);
}
