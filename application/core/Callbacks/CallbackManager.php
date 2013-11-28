<?php

namespace ManiaControl\Callbacks;

use ManiaControl\ManiaControl;

/**
 * Class for managing server and controller callbacks
 *
 * @author steeffeen & kremsy
 */
class CallbackManager {
	/**
	 * Constants
	 */
	// ManiaControl callbacks
	const CB_MC_1_SECOND = 'ManiaControl.1Second';
	const CB_MC_5_SECOND = 'ManiaControl.5Second';
	const CB_MC_1_MINUTE = 'ManiaControl.1Minute';
	const CB_MC_ONINIT = 'ManiaControl.OnInit';
	const CB_MC_CLIENTUPDATED = 'ManiaControl.ClientUpdated';
	const CB_MC_BEGINMAP = 'ManiaControl.BeginMap';
	const CB_MC_ENDMAP = 'ManiaControl.EndMap';
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
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $callbackListeners = array();
	private $scriptCallbackListener = array();
	private $last1Second = -1;
	private $last5Second = -1;
	private $last1Minute = -1;

	/**
	 * Construct callbacks manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->last1Second = time();
		$this->last5Second = time();
		$this->last1Minute = time();
		$this->last3Minute = time();
	}

	/**
	 * Register a new callback listener
	 *
	 * @param string $callbackName        	
	 * @param \ManiaControl\Callbacks\CallbackListener $listener        	
	 * @param string $method        	
	 * @return bool
	 */
	public function registerCallbackListener($callbackName, CallbackListener $listener, $method) {
		if (!method_exists($listener, $method)) {
			trigger_error(
					"Given listener (" . get_class($listener) . ") can't handle callback '{$callbackName}' (no method '{$method}')!");
			return false;
		}
		if (!array_key_exists($callbackName, $this->callbackListeners)) {
			$this->callbackListeners[$callbackName] = array();
		}
		array_push($this->callbackListeners[$callbackName], array($listener, $method));
		return true;
	}

	/**
	 * Register a new script callback listener
	 *
	 * @param string $callbackName        	
	 * @param CallbackListener $listener        	
	 * @param string $method        	
	 * @return bool
	 */
	public function registerScriptCallbackListener($callbackName, CallbackListener $listener, $method) {
		if (!method_exists($listener, $method)) {
			trigger_error(
					"Given listener (" . get_class($listener) .
							 ") can't handle script callback '{$callbackName}' (no method '{$method}')!");
			return false;
		}
		if (!array_key_exists($callbackName, $this->scriptCallbackListener)) {
			$this->scriptCallbackListener[$callbackName] = array();
		}
		array_push($this->scriptCallbackListener[$callbackName], array($listener, $method));
		return true;
	}

	/**
	 * Trigger a specific callback
	 *
	 * @param string $callbackName        	
	 * @param array $data        	
	 */
	public function triggerCallback($callbackName, array $callback) {
		if (!array_key_exists($callbackName, $this->callbackListeners)) {
			return;
		}
		foreach ($this->callbackListeners[$callbackName] as $listener) {
			call_user_func(array($listener[0], $listener[1]), $callback);
		}
	}

	/**
	 * Trigger a specific script callback
	 *
	 * @param string $callbackName        	
	 * @param array $callback        	
	 */
	public function triggerScriptCallback($callbackName, array $callback) {
		if (!array_key_exists($callbackName, $this->scriptCallbackListener)) {
			return;
		}
		foreach ($this->scriptCallbackListener[$callbackName] as $listener) {
			call_user_func(array($listener[0], $listener[1]), $callback);
		}
	}

	/**
	 * Trigger internal and manage server callbacks
	 */
	public function manageCallbacks() {
		$this->manageTimedCallbacks();
		
		// Get server callbacks
		if (!$this->maniaControl->client) {
			return;
		}
		$this->maniaControl->client->readCB();
		$callbacks = $this->maniaControl->client->getCBResponses();
		if (!is_array($callbacks)) {
			trigger_error("Error reading server callbacks. " . $this->maniaControl->getClientErrorText());
			return;
		}
		
		// Handle callbacks
		foreach ($callbacks as $index => $callback) {
			$callbackName = $callback[0];
			switch ($callbackName) {
				case 'ManiaPlanet.BeginMap':
					{
						$this->triggerCallback(self::CB_MC_BEGINMAP, $callback);
						break;
					}
				case 'ManiaPlanet.EndMap':
					{
						$this->triggerCallback(self::CB_MC_ENDMAP, $callback);
						break;
					}
				case self::CB_MP_MODESCRIPTCALLBACK:
					{
						$this->handleScriptCallback($callback);
						$this->triggerCallback(self::CB_MP_MODESCRIPTCALLBACK, $callback);
						break;
					}
				case self::CB_MP_MODESCRIPTCALLBACKARRAY:
					{
						$this->handleScriptCallback($callback);
						$this->triggerCallback(self::CB_MP_MODESCRIPTCALLBACKARRAY, $callback);
						break;
					}
				default:
					{
						$this->triggerCallback($callbackName, $callback);
						break;
					}
			}
		}
	}

	/**
	 * Handle the given script callback
	 *
	 * @param array $callback        	
	 */
	private function handleScriptCallback(array $callback) {
		$scriptCallbackData = $callback[1];
		$scriptCallbackName = $scriptCallbackData[0];
		switch ($scriptCallbackName) {
			case 'EndMap':
				{
					$this->triggerScriptCallback($scriptCallbackName, $scriptCallbackData);
					$this->triggerCallback(self::CB_MC_ENDMAP, $callback);
					break;
				}
			case 'LibXmlRpc_EndMap':
				{
					$this->triggerScriptCallback($scriptCallbackName, $scriptCallbackData);
					$this->triggerCallback(self::CB_MC_ENDMAP, $callback);
					break;
				}
			default:
				{
					$this->triggerScriptCallback($scriptCallbackName, $scriptCallbackData);
					break;
				}
		}
	}

	/**
	 * Manage recurring timed callbacks
	 */
	private function manageTimedCallbacks() {
		// 1 second
		if ($this->last1Second > time() - 1) {
			return;
		}
		$this->last1Second = time();
		$this->triggerCallback(self::CB_MC_1_SECOND, array(self::CB_MC_1_SECOND));
		
		// 5 second
		if ($this->last5Second > time() - 5) {
			return;
		}
		$this->last5Second = time();
		$this->triggerCallback(self::CB_MC_5_SECOND, array(self::CB_MC_5_SECOND));
		
		// 1 minute
		if ($this->last1Minute > time() - 60) {
			return;
		}
		$this->last1Minute = time();
		$this->triggerCallback(self::CB_MC_1_MINUTE, array(self::CB_MC_1_MINUTE));
	}
}

?>
