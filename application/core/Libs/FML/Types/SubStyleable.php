<?php

namespace FML\Types;

/**
 * Interface for Elements with substyle attribute
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface SubStyleable {

	/**
	 * Set sub style
	 *
	 * @param string $subStyle SubStyle name
	 * @return \FML\Types\SubStyleable|static
	 */
	public function setSubStyle($subStyle);

	/**
	 * Set style and sub style
	 *
	 * @param string $style    Style name
	 * @param string $subStyle SubStyle name
	 * @return \FML\Types\SubStyleable|static
	 */
	public function setStyles($style, $subStyle);
}
