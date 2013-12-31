<?php

namespace FML\Types;

/**
 * Interface for elements that support the action attribute
 *
 * @author steeffeen
 */
interface Actionable {

	/**
	 * Set action
	 *
	 * @param string $action
	 *        	Action Name
	 */
	public function setAction($action);
}
