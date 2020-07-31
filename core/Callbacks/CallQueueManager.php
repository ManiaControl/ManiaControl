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
	public function registerListening(CallQueueListener $listener, $methods, $errorMethod = null, $important = false) {
		if ($errorMethod != null && !CallQueueListening::checkValidCallback($listener, $errorMethod)) {
			trigger_error("Given Listener (" . get_class($listener) . ") can't handle Queue Call Callback (No Error Method '{$errorMethod}')!");
			return false;
		}

		if (!is_array($methods)) {
			$methods = array($methods);
		}

		foreach ($methods as $method) {
			if (!CallQueueListening::checkValidCallback($listener, $method)) {
				trigger_error("Given Listener (" . get_class($listener) . ") can't handle Queue Call Callback (No Method '{$method}')!");
				return false;
			}
		}

		foreach ($methods as $method) {
			// Build Call Queue Listening
			$listening = new CallQueueListening($listener, $method, $errorMethod);
			if ($important) {
				$this->addImportantListening($listening);
			} else {
				$this->addListening($listening);
			}
		}

		return true;
	}

	/**
	 * Adds an important Listening to the current list of managed queue calls at the front
	 *
	 * @param CallQueueListening $queueListening
	 */
	public function addImportantListening(CallQueueListening $queueListening) {
		array_unshift($this->queueListenings, $queueListening);
	}

	/**
	 * Adds a Listening to the current list of managed queue calls at the end
	 *
	 * @param CallQueueListening $queueListening
	 */
	public function addListening(CallQueueListening $queueListening) {
		array_push($this->queueListenings, $queueListening);
	}

	/**
	 * Checks, if one specific listening already has been queued for a call.
	 * Can only check for named functions.
	 * @param CallQueueListener $listener
	 * @param string            $method
	 * @return bool
	 */
	public function hasListening(CallQueueListener $listener, $method) {
		foreach ($this->queueListenings as $listening) {
			if ($listening->listener === $listener && $listening->method === $method) {
				return true;
			}
		}
		return false;
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
