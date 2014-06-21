<?php

namespace FML\Types;

/**
 * Interface for Elements with scriptevents attribute
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Scriptable {

	/**
	 * Set script events
	 *
	 * @param bool $scriptEvents Whether script events should be enabled
	 * @return \FML\Types\Scriptable|static
	 */
	public function setScriptEvents($scriptEvents);
}
