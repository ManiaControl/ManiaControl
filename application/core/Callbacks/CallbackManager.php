<?php

namespace ManiaControl\Callbacks;

use ManiaControl\Callbacks\Models\BaseCallback;
use ManiaControl\ManiaControl;

/**
 * Class for managing Server and ManiaControl Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CallbackManager {
	/*
	 * Constants
	 */
	// ManiaPlanet callbacks
	const CB_MP_SERVERSTART               = 'ManiaPlanet.ServerStart';
	const CB_MP_SERVERSTOP                = 'ManiaPlanet.ServerStop';
	const CB_MP_BEGINMATCH                = 'ManiaPlanet.BeginMatch';
	const CB_MP_ENDMATCH                  = 'ManiaPlanet.EndMatch';
	const CB_MP_BEGINMAP                  = 'ManiaPlanet.BeginMap';
	const CB_MP_ENDMAP                    = 'ManiaPlanet.EndMap';
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

	/*
	 * Public properties
	 */
	public $libXmlRpcCallbacks = null;
	public $shootManiaCallbacks = null;
	public $trackManiaCallbacks = null;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var Listening[][] $callbackListenings */
	private $callbackListenings = array();
	/** @var Listening[][] $scriptCallbackListenings */
	private $scriptCallbackListenings = array();

	/**
	 * Construct a new callbacks manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->libXmlRpcCallbacks  = new LibXmlRpcCallbacks($maniaControl, $this);
		$this->shootManiaCallbacks = new ShootManiaCallbacks($maniaControl, $this);
		$this->trackManiaCallbacks = new TrackManiaCallbacks($maniaControl, $this);
	}

	/**
	 * Register a new Callback Listener
	 *
	 * @param string           $callbackName
	 * @param CallbackListener $listener
	 * @param string           $method
	 * @return bool
	 */
	public function registerCallbackListener($callbackName, CallbackListener $listener, $method) {
		if (is_array($callbackName)) {
			$success = false;
			foreach ($callbackName as $callback) {
				if ($this->registerCallbackListener($callback, $listener, $method)) {
					$success = true;
				}
			}
			return $success;
		}

		if (!Listening::checkValidCallback($listener, $method)) {
			$listenerClass = get_class($listener);
			trigger_error("Given Listener '{$listenerClass}' can't handle Callback '{$callbackName}': No callable Method '{$method}'!");
			return false;
		}

		if (!array_key_exists($callbackName, $this->callbackListenings)) {
			$this->callbackListenings[$callbackName] = array();
		}

		$listening = new Listening($listener, $method);
		array_push($this->callbackListenings[$callbackName], $listening);

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
		if (is_array($callbackName)) {
			$success = false;
			foreach ($callbackName as $callback) {
				if ($this->registerScriptCallbackListener($callback, $listener, $method)) {
					$success = true;
				}
			}
			return $success;
		}

		if (!Listening::checkValidCallback($listener, $method)) {
			$listenerClass = get_class($listener);
			trigger_error("Given Listener '{$listenerClass}' can't handle Script Callback '{$callbackName}': No callable Method '{$method}'!");
			return false;
		}

		if (!array_key_exists($callbackName, $this->scriptCallbackListenings)) {
			$this->scriptCallbackListenings[$callbackName] = array();
		}

		$listening = new Listening($listener, $method);
		array_push($this->scriptCallbackListenings[$callbackName], $listening);

		return true;
	}

	/**
	 * Unregister a Callback Listener
	 *
	 * @param CallbackListener $listener
	 * @return bool
	 */
	public function unregisterCallbackListener(CallbackListener $listener) {
		return $this->removeCallbackListener($this->callbackListenings, $listener);
	}

	//TODO better name (used only in customvotesPlugin)

	/**
	 * Remove the Callback Listener from the given Listeners Array
	 *
	 * @param Listening[]      $listeningsArray
	 * @param CallbackListener $listener
	 * @return bool
	 */
	private function removeCallbackListener(array &$listeningsArray, CallbackListener $listener) {
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
	 * Unregister a single Callback Listening from an Callback Listener
	 *
	 * @param String           $callbackName
	 * @param CallbackListener $listener
	 * @return bool
	 */
	public function unregisterCallbackListening($callbackName, CallbackListener $listener) {
		$removed = false;
		foreach ($this->callbackListenings as &$listenings) {
			foreach ($listenings as $key => &$listening) {
				if ($key === $callbackName && $listening->listener === $listener) {
					unset($listenings[$key]);
					$removed = true;
				}
			}
		}
		return $removed;
	}

	/**
	 * Unregister a Script Callback Listener
	 *
	 * @param CallbackListener $listener
	 * @return bool
	 */
	public function unregisterScriptCallbackListener(CallbackListener $listener) {
		return $this->removeCallbackListener($this->scriptCallbackListenings, $listener);
	}

	/**
	 * Trigger internal Callbacks and manage Server Callbacks
	 */
	public function manageCallbacks() {
		// Manage Timings
		$this->maniaControl->getTimerManager()
		                   ->manageTimings();

		// Server Callbacks
		if (!$this->maniaControl->getClient()) {
			return;
		}

		// Handle callbacks
		$callbacks = $this->maniaControl->getClient()
		                                ->executeCallbacks();
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
			case self::CB_MP_BEGINMATCH:
				$this->triggerCallback($callbackName, $callback);
				break;
			case self::CB_MP_BEGINMAP:
				$this->maniaControl->getMapManager()
				                   ->handleBeginMap($callback);
				$this->triggerCallback($callbackName, $callback);
				break;
			case self::CB_MP_ENDMATCH:
				$this->triggerCallback($callbackName, $callback);
				break;
			case self::CB_MP_ENDMAP:
				$this->maniaControl->getMapManager()
				                   ->handleEndMap($callback);
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
	 * Trigger a specific Callback
	 *
	 * @param mixed $callback
	 */
	public function triggerCallback($callback) {
		if ($callback instanceof BaseCallback) {
			$callbackName = $callback->name;
		} else {
			$callbackName = $callback;
		}
		if (!array_key_exists($callbackName, $this->callbackListenings)) {
			return;
		}

		$params = func_get_args();
		if (!($callback instanceof BaseCallback)) {
			$params = array_slice($params, 1, null, true);
		}

		foreach ($this->callbackListenings[$callbackName] as $listening) {
			/** @var Listening $listening */
			$listening->triggerCallbackWithParams($params);
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
		$this->triggerCallback(Callbacks::SCRIPTCALLBACK, $scriptCallbackName, $scriptCallbackData[1]);
	}

	/**
	 * Trigger a specific Script Callback
	 *
	 * @param string $callbackName
	 */
	public function triggerScriptCallback($callbackName) {
		if (!array_key_exists($callbackName, $this->scriptCallbackListenings)) {
			return;
		}

		$params = func_get_args();
		$params = array_slice($params, 1, null, true);

		foreach ($this->scriptCallbackListenings[$callbackName] as $listening) {
			/** @var Listening $listening */
			$listening->triggerCallbackWithParams($params);
		}
	}
}
