<?php

namespace FML\Types;

/**
 * Interface for elements with ScriptEvents attribute
 *
 * @author steeffeen
 */
interface Scriptable {

	/**
	 * Set ScriptEvents
	 *
	 * @param bool $scriptEvents
	 *        	If Script Events should be enabled
	 */
	public function setScriptEvents($scriptEvents);
}
