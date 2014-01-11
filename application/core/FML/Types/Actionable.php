<?php

namespace FML\Types;

/**
 * Interface for Elements that support the Action Attribute
 *
 * @author steeffeen
 */
interface Actionable {
	/**
	 * Constants
	 */
	const ACTIONKEY_F5 = 1;
	const ACTIONKEY_F6 = 2;
	const ACTIONKEY_F7 = 3;
	const ACTIONKEY_F8 = 4;

	/**
	 * Set Action
	 *
	 * @param string $action Action Name
	 */
	public function setAction($action);

	/**
	 * Set Action Key
	 *
	 * @param int $actionKey Action Key Number
	 */
	public function setActionKey($actionKey);
}
