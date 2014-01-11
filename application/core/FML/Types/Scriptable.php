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
	 */
	public function setScriptEvents($scriptEvents);
}
