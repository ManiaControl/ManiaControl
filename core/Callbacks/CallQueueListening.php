<?php

namespace ManiaControl\Callbacks;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * Model Class for a Call Queue Listening
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2019 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CallQueueListening extends Listening implements UsageInformationAble {
	use UsageInformationTrait;

	private $errorMethod = null;

	/**
	 * Construct a new Call Queue Listening
	 *
	 * @param CallQueueListener $listener
	 * @param mixed             $method
	 * @param mixed             $errorMethod
	 */
	public function __construct(CallQueueListener $listener, $method, $errorMethod) {
		parent::__construct($listener, $method);
		if ($errorMethod != null) {
			$this->errorMethod = array($listener, $errorMethod);
		}
	}

	/**
	 * Trigger the Listener's Method
	 * @return bool
	 */
	public function triggerCallback() {
		$params = func_get_args();
		if ($this->triggerCallbackWithParams($params, false) === false) {
			if ($this->errorMethod != null) {
				call_user_func($this->errorMethod, $this->method);
			}

			return false;
		}

		return true;
	}

	/**
	 * Trigger the Listener's Method with the given Array of Params
	 *
	 * @param array $params
	 * @return mixed
	 */
	public function triggerCallbackWithParams(array $params, $callErrorMethod = true) {
		$result = call_user_func_array($this->getUserFunction(), $params);
		if ($callErrorMethod && $result === false) {
			if ($this->errorMethod != null) {
				call_user_func($this->errorMethod, $this->getUserFunction());
			}

			return false;
		}

		return $result;
	}
}
