<?php

namespace FML\Types;

/**
 * Interface for Elements with ScriptEvents Attribute
 *
 * @author steeffeen
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
