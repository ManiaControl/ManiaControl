<?php

namespace ManiaControl\Callbacks;

use ManiaControl\ManiaControl;

/**
 * Class for managing Timers
 *
 * @author kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class TimerManager {
	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $timerListenings = array();

	/**
	 * Construct a new Timer Manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Registers a One Time Listening
	 *
	 * @param TimerListener $listener
	 * @param               $method
	 * @param               $time
	 */
	public function registerOneTimeListening(TimerListener $listener, $method, $time) {
		$this->registerTimerListening($listener, $method, $time, true);
	}

	/**
	 * Unregisters a Timer Listening
	 * @param TimerListener $listener
	 * @param               $method
	 * @return bool
	 */
	public function unregisterTimerListening(TimerListener $listener, $method){
		foreach($this->timerListenings as $key => $listening){
			if($listening->listener == $listener && $listening->method == $method){
				unset($this->timerListenings[$key]);
				return true;
			}
		}
		return false;
	}

	/**
	 * Registers a Timing Listening, note < 10ms it can get inaccurate
	 *
	 * @param TimerListener $listener
	 * @param               $method
	 * @param               $time
	 * @return bool
	 */
	public function registerTimerListening(TimerListener $listener, $method, $time, $oneTime = false) {
		if ((!is_string($method) || !method_exists($listener, $method)) && !is_callable($method)) {
			trigger_error("Given listener (" . get_class($listener) . ") can't handle timer (no method '{$method}')!");
			return false;
		}

		//Init the Timer Listening
		// TODO: extra model class
		$listening              = new \stdClass();
		$listening->listener    = $listener;
		$listening->method      = $method;
		$listening->deltaTime   = $time / 1000;
		$listening->oneTime     = $oneTime;
		if($oneTime){
			$listening->lastTrigger = microtime(true);
		}else{
			$listening->lastTrigger = -1;
		}
		array_push($this->timerListenings, $listening);

		return true;
	}

	/**
	 * Remove a Timer Listener
	 *
	 * @param TimerListener $listener
	 * @return bool
	 */
	public function unregisterTimerListenings(TimerListener $listener) {
		$removed = false;
		foreach($this->timerListenings as $key => &$listening) {
			if ($listening->listener != $listener) {
				continue;
			}
			unset($this->timerListenings[$key]);
			$removed = true;
		}
		return $removed;
	}

	/**
	 * Manage the Timings on every ms
	 */
	public function manageTimings() {
		$time = microtime(true);
		foreach($this->timerListenings as $key => &$listening) {

			if (($listening->lastTrigger + $listening->deltaTime) <= $time) {
				//Increase the lastTrigger time manually (to improve accuracy)
				if ($listening->lastTrigger != -1) {
					$listening->lastTrigger += $listening->deltaTime;
				} else {
					//Initialize Timer
					$listening->lastTrigger = $time;
				}

				//Unregister one time Listening
				if ($listening->oneTime == true) {
					unset($this->timerListenings[$key]);
				}

				//Call the User func (at the end to avoid endless loops)
				if (is_callable($listening->method)) {
					call_user_func($listening->method, $time);
				} else {
					call_user_func(array($listening->listener, $listening->method), $time);
				}
			}
		}
	}
} 
