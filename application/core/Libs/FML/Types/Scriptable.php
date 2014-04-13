<?php

namespace FML\Types;

/**
 * Interface for Elements with ScriptEvents Attribute
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Scriptable {

	/**
	 * Set ScriptEvents
	 *
	 * @param bool $scriptEvents Whether Script Events should be enabled
	 * @return \FML\Types\Scriptable
	 */
	public function setScriptEvents($scriptEvents);
}
