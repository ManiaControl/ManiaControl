<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 30.01.14
 * Time: 21:11
 */

namespace ManiaControl\Callbacks;


use ManiaControl\ManiaControl;

class TimerManager {
	private $maniaControl = null;
	private $timerListenings = array();

	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Registers a Timing Listening, note < 10ms it can get inaccurate
	 *
	 * @param TimerListener $listener
	 * @param               $method
	 * @param               $time
	 * @return bool
	 */
	public function registerTimerListening(TimerListener $listener, $method, $time) {
		if (!method_exists($listener, $method)) {
			trigger_error("Given listener (" . get_class($listener) . ") can't handle timer (no method '{$method}')!");
			return false;
		}
		array_push($this->timerListenings, array("Listener" => $listener, "Method" => $method, "DeltaTime" => ($time / 1000), "LastTrigger" => microtime(true)));
		return true;

	}

	/**
	 * Manage the Timings on every ms
	 */
	public function manageTimings() {
		$time = microtime(true);
		foreach($this->timerListenings as $key => $listening) {
			if ($listening["LastTrigger"] + $listening["DeltaTime"] < $time) {
				call_user_func(array($listening["Listener"], $listening["Method"]), $time);
				$this->timerListenings[$key]["LastTrigger"] += ($listening["DeltaTime"]);
			}
		}
	}

} 