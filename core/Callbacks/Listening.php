<?php

namespace ManiaControl\Callbacks;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * Model Class for a Basic Listening
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Listening implements UsageInformationAble {
	use UsageInformationTrait;
	
	/*
	 * Public Properties
	 */
	public $listener = null;
	public $method   = null;

	/**
	 * Construct a new Timer Listening
	 *
	 * @param object $listener
	 * @param mixed  $method
	 */
	public function __construct($listener, $method) {
		$this->listener = $listener;
		$this->method   = $method;
	}

	/**
	 * Check if the given Listener and Method build a valid Callback
	 *
	 * @param object $listener
	 * @param mixed  $method
	 * @return bool
	 */
	public static function checkValidCallback($listener, $method) {
		if (is_callable($method)) {
			return true;
		}
		$listenerCallback = array($listener, $method);
		if (is_callable($listenerCallback)) {
			return true;
		}
		return false;
	}

	/**
	 * Trigger the Listener's Method
	 */
	public function triggerCallback() {
		$params = func_get_args();
		$this->triggerCallbackWithParams($params);
	}

	/**
	 * Trigger the Listener's Method with the given Array of Params
	 *
	 * @param array $params
	 * @return mixed
	 */
	public function triggerCallbackWithParams(array $params) {
		return call_user_func_array($this->getUserFunction(), $params);
	}

	/**
	 * Get the Callable User Function
	 *
	 * @return callable
	 */
	public function getUserFunction() {
		if (is_callable($this->method)) {
			return $this->method;
		}
		return array($this->listener, $this->method);
	}
}
