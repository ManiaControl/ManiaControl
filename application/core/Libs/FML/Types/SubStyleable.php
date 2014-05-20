<?php

namespace FML\Types;

/**
 * Interface for Elements with SubStyle Attribute
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface SubStyleable {

	/**
	 * Set SubStyle
	 *
	 * @param string $subStyle SubStyle Name
	 * @return \FML\Types\SubStyleable
	 */
	public function setSubStyle($subStyle);

	/**
	 * Set Style and SubStyle
	 *
	 * @param string $style    Style Name
	 * @param string $subStyle SubStyle Name
	 * @return \FML\Types\SubStyleable
	 */
	public function setStyles($style, $subStyle);
}
