<?php

namespace ManiaControl\Callbacks;


use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

/**
 * Class for managing Echo Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class EchoManager implements CallbackListener, EchoListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var Listening[] $echoListenings */
	private $echoListenings = array();

	/**
	 * Create a new Echo Handler Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_ECHO, $this, 'handleEchos');
	}

	/**
	 * Sends an Echo Message
	 *
	 * @param string $name
	 * @param mixed  $data (can be array, object or string)
	 * @return bool
	 * @throws \Maniaplanet\DedicatedServer\InvalidArgumentException
	 */
	public function sendEcho($name, $data) {
		if (is_string($data)) {
			$success = $this->maniaControl->getClient()->dedicatedEcho($data, $name);
		} else {
			$success = $this->maniaControl->getClient()->dedicatedEcho(json_encode($data), $name);
		}

		return $success;
	}

	/**
	 * Register a new Echo Listener
	 *
	 * @param string       $callbackName
	 * @param EchoListener $listener
	 * @param string       $method
	 * @return bool
	 */
	public function registerEchoListener($echoName, EchoListener $listener, $method) {
		if (!Listening::checkValidCallback($listener, $method)) {
			$listenerClass = get_class($listener);
			trigger_error("Given Listener '{$listenerClass}' can't handle Callback '{$echoName}': No callable Method '{$method}'!");
			return false;
		}

		if (!array_key_exists($echoName, $this->echoListenings)) {
			$this->echoListenings[$echoName] = array();
		}

		$listening                       = new Listening($listener, $method);
		$this->echoListenings[$echoName] = $listening;
		return true;
	}

	/**
	 * Unregister a Echo Listener
	 *
	 * @param EchoListener $listener
	 * @return bool
	 */
	public function unregisterEchoListener(EchoListener $listener) {
		return $this->removeEchoListener($this->echoListenings, $listener);
	}

	/**
	 * Remove the Echo Listener from the given Listeners Array
	 *
	 * @param Listening[]  $listeningsArray
	 * @param EchoListener $listener
	 * @return bool
	 */
	private function removeEchoListener(array &$listeningsArray, EchoListener $listener) {
		$removed = false;
		foreach ($listeningsArray as &$listenings) {
			foreach ($listenings as $key => &$listening) {
				if ($listening->listener === $listener) {
					unset($listenings[$key]);
					$removed = true;
				}
			}
		}
		return $removed;
	}


	/**
	 * Trigger a specific Callback
	 *
	 * @param mixed $callback
	 */
	public function triggerEchoCallback($callbackName) {
		if (!array_key_exists($callbackName, $this->echoListenings)) {
			return;
		}


		$params = func_get_args();
		$params = array_slice($params, 1, null, true);

		foreach ($this->echoListenings[$callbackName] as $listening) {
			/** @var Listening $listening */
			$listening->triggerCallbackWithParams($params);
		}
	}

	/**
	 * Handle the given Callback
	 *
	 * @param array $callback
	 */
	public function handleEchos($param) {
		$name = $param[1][0];
		if (is_object($decode = json_decode($param[1][1]))) {
			$message = $decode;
		} else {
			$message = $param[1][1];
		}

		switch ($name) {
			case Callbacks::ONRESTART:
			case Callbacks::ONREBOOT:
				$this->maniaControl->reboot($message);
				break;
			default:
				$this->triggerEchoCallback($name, $message);
		}
	}
}