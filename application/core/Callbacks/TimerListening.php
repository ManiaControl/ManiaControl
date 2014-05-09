<?php

namespace ManiaControl\Callbacks;

/**
 * Model Class for a TimerListening
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class TimerListening {
	/*
	 * Public Properties
	 */
	public $listener = null;
	public $method = null;
	public $deltaTime = null;
	public $oneTime = null;
	public $lastTrigger = null;
	public $instantCall = null;

	/**
	 * Construct a new Timer Listening
	 *
	 * @param TimerListener $listener
	 * @param string        $method
	 * @param float         $deltaTime
	 * @param bool          $oneTime
	 * @param bool          $instantCall
	 */
	public function __construct(TimerListener $listener, $method, $deltaTime, $oneTime = false, $instantCall = true) {
		$this->listener    = $listener;
		$this->method      = $method;
		$this->deltaTime   = $deltaTime / 1000.;
		$this->oneTime     = (bool)$oneTime;
		$this->instantCall = (bool)$instantCall;
		if (!$this->instantCall) {
			$this->lastTrigger = microtime(true);
		}
	}

	/**
	 * Increase last Trigger Time
	 */
	public function tick() {
		$this->lastTrigger += $this->deltaTime;
	}

	/**
	 * Trigger the Listener's Method
	 *
	 * @param float $time
	 */
	public function triggerCallback($time) {
		call_user_func($this->getUserFunction(), $time);
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

	/**
	 * Check if the desired Time is reached
	 *
	 * @param float $time
	 * @return bool
	 */
	public function isTimeReached($time = null) {
		if ($this->lastTrigger === null) {
			return true;
		}
		if (!$time) {
			$time = microtime(true);
		}
		return ($this->lastTrigger + $this->deltaTime <= $time);
	}
}
