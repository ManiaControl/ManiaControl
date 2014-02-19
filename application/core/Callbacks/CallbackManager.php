<?php

namespace ManiaControl\Callbacks;

use ManiaControl\ManiaControl;

/**
 * Class for managing Server and ManiaControl Callbacks
 *
 * @author steeffeen & kremsy
 */
class CallbackManager {
	/**
	 * Constants
	 */
	// ManiaControl callbacks
	const CB_ONINIT        = 'ManiaControl.OnInit';
	const CB_AFTERINIT     = 'ManiaControl.AfterInit';
	const CB_ONSHUTDOWN    = 'ManiaControl.OnShutdown';
	const CB_CLIENTUPDATED = 'ManiaControl.ClientUpdated';
	const CB_BEGINMAP      = 'ManiaControl.BeginMap';
	const CB_ENDMAP        = 'ManiaControl.EndMap';

	// ManiaPlanet callbacks
	const CB_MP_SERVERSTART               = 'ManiaPlanet.ServerStart';
	const CB_MP_SERVERSTOP                = 'ManiaPlanet.ServerStop';
	const CB_MP_BEGINMATCH                = 'ManiaPlanet.BeginMatch';
	const CB_MP_ENDMATCH                  = 'ManiaPlanet.EndMatch';
	const CB_MP_MAPLISTMODIFIED           = 'ManiaPlanet.MapListModified';
	const CB_MP_ECHO                      = 'ManiaPlanet.Echo';
	const CB_MP_BILLUPDATED               = 'ManiaPlanet.BillUpdated';
	const CB_MP_PLAYERCHAT                = 'ManiaPlanet.PlayerChat';
	const CB_MP_PLAYERCONNECT             = 'ManiaPlanet.PlayerConnect';
	const CB_MP_PLAYERDISCONNECT          = 'ManiaPlanet.PlayerDisconnect';
	const CB_MP_PLAYERMANIALINKPAGEANSWER = 'ManiaPlanet.PlayerManialinkPageAnswer';
	const CB_MP_PLAYERINFOCHANGED         = 'ManiaPlanet.PlayerInfoChanged';
	const CB_MP_PLAYERALLIESCHANGED       = 'ManiaPlanet.PlayerAlliesChanged';
	const CB_MP_VOTEUPDATED               = 'ManiaPlanet.VoteUpdated';
	const CB_MP_STATUSCHANGED             = 'ManiaPlanet.StatusChanged';
	const CB_MP_MODESCRIPTCALLBACK        = 'ManiaPlanet.ModeScriptCallback';
	const CB_MP_MODESCRIPTCALLBACKARRAY   = 'ManiaPlanet.ModeScriptCallbackArray';
	const CB_MP_TUNNELDATARECEIVED        = 'ManiaPlanet.TunnelDataReceived';

	// TrackMania callbacks
	const CB_TM_PLAYERCHECKPOINT  = 'TrackMania.PlayerCheckpoint';
	const CB_TM_PLAYERFINISH      = 'TrackMania.PlayerFinish';
	const CB_TM_PLAYERINCOHERENCE = 'TrackMania.PlayerIncoherence';

	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $callbackListeners = array();
	private $scriptCallbackListener = array();
	private $last1Second = -1;
	private $last5Second = -1;
	private $last1Minute = -1;
	private $mapEnded = false;
	private $mapBegan = false;

	/**
	 * Construct a new Callbacks Manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->last1Second  = time();
		$this->last5Second  = time();
		$this->last1Minute  = time();

	}

	/**
	 * Register a new Callback Listener
	 *
	 * @param string                                   $callbackName
	 * @param \ManiaControl\Callbacks\CallbackListener $listener
	 * @param string                                   $method
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
	 * @param string           $callbackName
	 * @param CallbackListener $listener
	 * @param string           $method
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
		foreach($this->callbackListeners as &$listeners) {
			foreach($listeners as $key => &$listenerCallback) {
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
		foreach($this->scriptCallbackListener as &$listeners) {
			foreach($listeners as $key => &$listenerCallback) {
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
	 * @param array  $callback
	 */
	public function triggerCallback($callbackName, array $callback) {
		if (!array_key_exists($callbackName, $this->callbackListeners)) {
			return;
		}
		foreach($this->callbackListeners[$callbackName] as $listener) {
			call_user_func(array($listener[0], $listener[1]), $callback);
		}
	}

	/**
	 * Trigger a specific Script Callback
	 *
	 * @param string $callbackName
	 * @param array  $callback
	 */
	public function triggerScriptCallback($callbackName, array $callback) {
		if (!array_key_exists($callbackName, $this->scriptCallbackListener)) {
			return;
		}
		foreach($this->scriptCallbackListener[$callbackName] as $listener) {
			call_user_func(array($listener[0], $listener[1]), $callback);
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
		foreach($callbacks as $callback) {
			$callbackName = $callback[0];
			switch($callbackName) {
				case 'ManiaPlanet.BeginMap':
					if (!$this->mapBegan) {
						$this->triggerCallback(self::CB_BEGINMAP, $callback);
						$this->mapBegan = true;
						$this->mapEnded = false;
					}
					break;
				case 'ManiaPlanet.EndMatch': //TODO temporary fix
				case 'ManiaPlanet.EndMap':
					if (!$this->mapEnded) {
						$this->triggerCallback(self::CB_ENDMAP, $callback);
						$this->mapEnded = true;
						$this->mapBegan = false;
					}
					break;
				case self::CB_MP_MODESCRIPTCALLBACK:
					$this->handleScriptCallback($callback);
					$this->triggerCallback(self::CB_MP_MODESCRIPTCALLBACK, $callback);
					break;
				case self::CB_MP_MODESCRIPTCALLBACKARRAY:
					$this->handleScriptCallback($callback);
					$this->triggerCallback(self::CB_MP_MODESCRIPTCALLBACKARRAY, $callback);
					break;
				default:
					$this->triggerCallback($callbackName, $callback);
					break;
			}
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
		switch($scriptCallbackName) {
			case 'BeginMap':
			case 'LibXmlRpc_BeginMap':
				$this->triggerScriptCallback($scriptCallbackName, $scriptCallbackData);
				if (!$this->mapBegan) {
					$this->triggerCallback(self::CB_BEGINMAP, $callback);
					$this->mapBegan = true;
					$this->mapEnded = false;
				}
				break;
			case 'EndMap':
			case 'LibXmlRpc_EndMap':
				$this->triggerScriptCallback($scriptCallbackName, $scriptCallbackData);
				if (!$this->mapEnded) {
					$this->triggerCallback(self::CB_ENDMAP, $callback);
					$this->mapEnded = true;
					$this->mapBegan = false;
				}
				break;
			default:
				$this->triggerScriptCallback($scriptCallbackName, $scriptCallbackData);
				break;
		}
	}
}
