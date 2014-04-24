<?php

namespace ManiaControl\Callbacks;

use ManiaControl\ManiaControl;

/**
 * Class for managing Server and ManiaControl Callbacks
 *
 * @author steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CallbackManager {
	/*
	 * Constants
	 */
	// ManiaControl callbacks
	const CB_ONINIT = 'ManiaControl.OnInit';
	const CB_AFTERINIT = 'ManiaControl.AfterInit';
	const CB_ONSHUTDOWN = 'ManiaControl.OnShutdown';
	
	// ManiaPlanet callbacks
	const CB_MP_SERVERSTART = 'ManiaPlanet.ServerStart';
	const CB_MP_SERVERSTOP = 'ManiaPlanet.ServerStop';
	const CB_MP_BEGINMATCH = 'ManiaPlanet.BeginMatch';
	const CB_MP_ENDMATCH = 'ManiaPlanet.EndMatch';
	const CB_MP_MAPLISTMODIFIED = 'ManiaPlanet.MapListModified';
	const CB_MP_ECHO = 'ManiaPlanet.Echo';
	const CB_MP_BILLUPDATED = 'ManiaPlanet.BillUpdated';
	const CB_MP_PLAYERCHAT = 'ManiaPlanet.PlayerChat';
	const CB_MP_PLAYERCONNECT = 'ManiaPlanet.PlayerConnect';
	const CB_MP_PLAYERDISCONNECT = 'ManiaPlanet.PlayerDisconnect';
	const CB_MP_PLAYERMANIALINKPAGEANSWER = 'ManiaPlanet.PlayerManialinkPageAnswer';
	const CB_MP_PLAYERINFOCHANGED = 'ManiaPlanet.PlayerInfoChanged';
	const CB_MP_PLAYERALLIESCHANGED = 'ManiaPlanet.PlayerAlliesChanged';
	const CB_MP_VOTEUPDATED = 'ManiaPlanet.VoteUpdated';
	const CB_MP_STATUSCHANGED = 'ManiaPlanet.StatusChanged';
	const CB_MP_MODESCRIPTCALLBACK = 'ManiaPlanet.ModeScriptCallback';
	const CB_MP_MODESCRIPTCALLBACKARRAY = 'ManiaPlanet.ModeScriptCallbackArray';
	const CB_MP_TUNNELDATARECEIVED = 'ManiaPlanet.TunnelDataReceived';
	
	// TrackMania callbacks
	const CB_TM_PLAYERCHECKPOINT = 'TrackMania.PlayerCheckpoint';
	const CB_TM_PLAYERFINISH = 'TrackMania.PlayerFinish';
	const CB_TM_PLAYERINCOHERENCE = 'TrackMania.PlayerIncoherence';
	
	/*
	 * Public Properties
	 */
	public $shootManiaCallbacks = null;
	public $libXmlRpcCallbacks = null;

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $callbackListeners = array();
	private $scriptCallbackListener = array();

	/**
	 * Construct a new Callbacks Manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		$this->shootManiaCallbacks = new ShootManiaCallbacks($maniaControl, $this);
		$this->libXmlRpcCallbacks = new LibXmlRpcCallbackManager($maniaControl, $this);
	}

	/**
	 * Register a new Callback Listener
	 *
	 * @param string $callbackName
	 * @param \ManiaControl\Callbacks\CallbackListener $listener
	 * @param string $method
	 * @return bool
	 */
	public function registerCallbackListener($callbackName, CallbackListener $listener, $method) {
		if (!method_exists($listener, $method)) {
			trigger_error("Given listener (" . get_class($listener) . ") can't handle callback '{$callbackName}' (no method '{$method}')!");
			return false;
		}
		if (!array_key_exists($callbackName, $this->callbackListeners)) {
			$this->callbackListeners[$callbackName] = array();
		}
		array_push($this->callbackListeners[$callbackName], array($listener, $method));
		return true;
	}

	/**
	 * Register a new Script Callback Listener
	 *
	 * @param string $callbackName
	 * @param CallbackListener $listener
	 * @param string $method
	 * @return bool
	 */
	public function registerScriptCallbackListener($callbackName, CallbackListener $listener, $method) {
		if (!method_exists($listener, $method)) {
			trigger_error("Given listener (" . get_class($listener) . ") can't handle script callback '{$callbackName}' (no method '{$method}')!");
			return false;
		}
		if (!array_key_exists($callbackName, $this->scriptCallbackListener)) {
			$this->scriptCallbackListener[$callbackName] = array();
		}
		array_push($this->scriptCallbackListener[$callbackName], array($listener, $method));
		return true;
	}

	/**
	 * Remove a Callback Listener
	 *
	 * @param CallbackListener $listener
	 * @return bool
	 */
	public function unregisterCallbackListener(CallbackListener $listener) {
		$removed = false;
		foreach ($this->callbackListeners as &$listeners) {
			foreach ($listeners as $key => &$listenerCallback) {
				if ($listenerCallback[0] != $listener) {
					continue;
				}
				unset($listeners[$key]);
				$removed = true;
			}
		}
		return $removed;
	}

	/**
	 * Remove a Script Callback Listener
	 *
	 * @param CallbackListener $listener
	 * @return bool
	 */
	public function unregisterScriptCallbackListener(CallbackListener $listener) {
		$removed = false;
		foreach ($this->scriptCallbackListener as &$listeners) {
			foreach ($listeners as $key => &$listenerCallback) {
				if ($listenerCallback[0] != $listener) {
					continue;
				}
				unset($listeners[$key]);
				$removed = true;
			}
		}
		return $removed;
	}

	/**
	 * Trigger a specific Callback
	 *
	 * @param string $callbackName
	 */
	public function triggerCallback($callbackName) {
		if (!array_key_exists($callbackName, $this->callbackListeners)) {
			return;
		}
		$params = func_get_args();
		$params = array_slice($params, 1, count($params), true);
		foreach ($this->callbackListeners[$callbackName] as $listener) {
			call_user_func_array(array($listener[0], $listener[1]), $params);
		}
	}

	/**
	 * Trigger a specific Script Callback
	 *
	 * @param string $callbackName
	 */
	public function triggerScriptCallback($callbackName) {
		if (!array_key_exists($callbackName, $this->scriptCallbackListener)) {
			return;
		}
		$params = func_get_args();
		$params = array_slice($params, 1, count($params), true);
		foreach ($this->scriptCallbackListener[$callbackName] as $listener) {
			call_user_func_array(array($listener[0], $listener[1]), $params);
		}
	}

	/**
	 * Trigger internal Callbacks and manage Server Callbacks
	 */
	public function manageCallbacks() {
		// Manage Timings
		$this->maniaControl->timerManager->manageTimings();
		
		// Server Callbacks
		if (!$this->maniaControl->client) {
			return;
		}
		
		$callbacks = $this->maniaControl->client->executeCallbacks();
		
		// Handle callbacks
		foreach ($callbacks as $callback) {
			$this->handleCallback($callback);
		}
	}

	/**
	 * Handle the given Callback
	 *
	 * @param array $callback
	 */
	private function handleCallback(array $callback) {
		$callbackName = $callback[0];
		switch ($callbackName) {
			case 'ManiaPlanet.BeginMatch':
				if ($this->maniaControl->mapManager->getCurrentMap()->getGame() == 'sm') {
					$this->triggerCallback($callbackName, $callback);
					break;
				}
			case 'ManiaPlanet.BeginMap':
				$this->maniaControl->mapManager->handleBeginMap($callback);
				$this->triggerCallback($callbackName, $callback);
				break;
			case 'ManiaPlanet.EndMatch': // TODO temporary fix
				if ($this->maniaControl->mapManager->getCurrentMap()->getGame() == 'sm') {
					$this->triggerCallback($callbackName, $callback);
					break;
				}
			case 'ManiaPlanet.EndMap':
				$this->maniaControl->mapManager->handleEndMap($callback);
				$this->triggerCallback($callbackName, $callback);
				break;
			case self::CB_MP_MODESCRIPTCALLBACK:
				$this->handleScriptCallback($callback);
				$this->triggerCallback($callbackName, $callback);
				break;
			case self::CB_MP_MODESCRIPTCALLBACKARRAY:
				$this->handleScriptCallback($callback);
				$this->triggerCallback($callbackName, $callback);
				break;
			default:
				$this->triggerCallback($callbackName, $callback);
				break;
		}
	}

	/**
	 * Handle the given Script Callback
	 *
	 * @param array $callback
	 */
	private function handleScriptCallback(array $callback) {
		$scriptCallbackData = $callback[1];
		$scriptCallbackName = $scriptCallbackData[0];
		$this->triggerScriptCallback($scriptCallbackName, $scriptCallbackData);
		$this->triggerCallback(Callbacks::ScriptCallback, $scriptCallbackName, $scriptCallbackData[1]);
	}
}
