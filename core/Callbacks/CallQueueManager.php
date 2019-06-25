<?php

namespace ManiaControl\Callbacks;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

/**
 * Class for managing queued calls.
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2019 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CallQueueManager implements UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var CallQueueListening[] $queueListenings */
	private $queueListenings = array();

	/**
	 * Construct a new Call Queue Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Register a Call Queue Listening
	 *
	 * @param CallQueueListener $listener
	 * @param mixed             $methods
	 * @param mixed             $errorMethod
	 * @return bool
	 */
	public function registerListening(CallQueueListener $listener, $methods, $errorMethod) {
		if (!CallQueueListening::checkValidCallback($listener, $errorMethod)) {
			trigger_error("Given Listener (" . get_class($listener) . ") can't handle Queue Call Callback (No Error Method '{$errorMethod}')!");
			return false;
		}

		if (is_string($methods)) {
			$methods = array($methods);
		}

		assert(is_array($methods));

		foreach ($methods as $method) {
			if (!CallQueueListening::checkValidCallback($listener, $method)) {
				trigger_error("Given Listener (" . get_class($listener) . ") can't handle Queue Call Callback (No Method '{$method}')!");
				return false;
			}

			// Build Call Queue Listening
			$listening = new CallQueueListening($listener, $method, $errorMethod);
			$this->addListening($listening);
		}

		return true;
	}

	/**
	 * Add a Listening to the current List of managed queue calls
	 *
	 * @param CallQueueListening $queueListening
	 */
	public function addListening(CallQueueListening $queueListening) {
		array_push($this->queueListenings, $queueListening);
	}

	/**
	 * Manage one of the queued calls
	 */
	public function manageCallQueue() {
		if (!empty($this->queueListenings)) {
			$listening = array_shift($this->queueListenings);
			$listening->triggerCallback();
		}
	}
} 
