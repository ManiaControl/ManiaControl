<?php

namespace FML\Types;

/**
 * Interface for Elements with AutoNewLine Attribute
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface NewLineable {

	/**
	 * Set Auto New Line
	 *
	 * @param bool $autoNewLine Whether the Control should insert New Lines automatically
	 * @return \FML\Types\NewLineable
	 */
	public function setAutoNewLine($autoNewLine);
}
