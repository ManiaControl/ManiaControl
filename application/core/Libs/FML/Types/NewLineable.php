<?php

namespace FML\Types;

/**
 * Interface for Elements with autonewline attribute
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface NewLineable {

	/**
	 * Set auto new line
	 *
	 * @param bool $autoNewLine Whether the Control should insert new lines automatically
	 * @return \FML\Types\NewLineable|static
	 */
	public function setAutoNewLine($autoNewLine);
}
