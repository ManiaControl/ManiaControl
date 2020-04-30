<?php

namespace ManiaControl\Callbacks;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

/**
 * Class for managing Timed Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class TimerManager implements UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var TimerListening[] $timerListenings */
	private $timerListenings = array();

	/**
	 * Construct a new Timer Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Registers a One Time Listening
	 *
	 * @param TimerListener $listener
	 * @param string        $method
	 * @param float         $milliSeconds
	 */
	public function registerOneTimeListening(TimerListener $listener, $method, $milliSeconds) {
		$this->registerTimerListening($listener, $method, $milliSeconds, true);
	}

	/**
	 * Register a Timer Listening, note < 10ms it can get inaccurate
	 *
	 * @param TimerListener $listener
	 * @param string        $method
	 * @param float         $milliSeconds
	 * @param bool          $oneTime
	 * @return bool
	 */
	public function registerTimerListening(TimerListener $listener, $method, $milliSeconds, $oneTime = false) {
		if ((!is_string($method) || !method_exists($listener, $method)) && !is_callable($method)) {
			trigger_error("Given Listener (" . get_class($listener) . ") can't handle Timer Callback (No Method '{$method}')!");
			return false;
		}

		// Build Timer Listening
		$listening = new TimerListening($listener, $method, $milliSeconds, $oneTime);
		$this->addTimerListening($listening);

		return true;
	}

	/**
	 * Add a Listening to the current List of managed Timers
	 *
	 * @param TimerListening $timerListening
	 */
	public function addTimerListening(TimerListening $timerListening) {
		array_push($this->timerListenings, $timerListening);
	}

	/**
	 * Update the deltaTime of a Timer Listening
	 * 
	 * @param TimerListener   $listener
	 * @param string|callable $method
	 * @param float           $milliSeconds
	 */
	public function updateTimerListening(TimerListener $listener, $method, $milliSeconds) {
		$updated = false;
		foreach ($this->timerListenings as $key => &$listening) {
			if ($listening->listener === $listener && $listening->method === $method) {
				$listening->setDeltaTime($milliSeconds);
				$updated = true;
			}
		}
		return $updated;
	}

	/**
	 * Unregister a Timer Listening
	 *
	 * @param TimerListener $listener
	 * @param string        $method
	 * @return bool
	 */
	public function unregisterTimerListening(TimerListener $listener, $method) {
		$removed = false;
		foreach ($this->timerListenings as $key => &$listening) {
			if ($listening->listener === $listener && $listening->method === $method) {
				unset($this->timerListenings[$key]);
				$removed = true;
			}
		}
		return $removed;
	}

	/**
	 * Unregister a Timer Listener
	 *
	 * @param TimerListener $listener
	 * @return bool
	 */
	public function unregisterTimerListenings(TimerListener $listener) {
		$removed = false;
		foreach ($this->timerListenings as $key => &$listening) {
			if ($listening->listener === $listener) {
				unset($this->timerListenings[$key]);
				$removed = true;
			}
		}
		return $removed;
	}

	/**
	 * Manage the Timings on every ms
	 */
	public function manageTimings() {
		$time = microtime(true);

		foreach ($this->timerListenings as $key => $listening) {
			/** @var TimerListening $listening */

			if (!$listening->isTimeReached($time)) {
				continue;
			}

			if ($listening->oneTime) {
				// Unregister one time Listening
				unset($this->timerListenings[$key]);
			}

			$listening->tick();

			// Call the User Function
			$listening->triggerCallback($time);
		}
	}
} 
