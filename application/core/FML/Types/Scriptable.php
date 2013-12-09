<?php

namespace FML\Types;

/**
 * Interface for elements with ScriptEvents attribute
 *
 * @author steeffeen
 */
interface Scriptable {

	/**
	 * Set scriptevents
	 *
	 * @param bool $scriptEvents
	 */
	public function setScriptEvents($scriptEvents);
}
