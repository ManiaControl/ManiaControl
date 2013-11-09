<?php

namespace ManiaControl;

/**
 * Class for handling server callbacks
 *
 * @author steeffeen
 */
class Callbacks {
	/**
	 * Constants
	 */
	// ManiaControl callbacks
	const CB_IC_1_SECOND = 'ManiaControl.1Second';
	const CB_IC_5_SECOND = 'ManiaControl.5Second';
	const CB_IC_1_MINUTE = 'ManiaControl.1Minute';
	const CB_IC_3_MINUTE = 'ManiaControl.3Minute';
	const CB_IC_ONINIT = 'ManiaControl.OnInit';
	const CB_IC_CLIENTUPDATED = 'ManiaControl.ClientUpdated';
	const CB_IC_BEGINMAP = 'ManiaControl.BeginMap';
	const CB_IC_ENDMAP = 'ManiaControl.EndMap';
	// ManiaPlanet callbacks
	const CB_MP_SERVERSTART = 'ManiaPlanet.ServerStart';
	const CB_MP_SERVERSTOP = 'ManiaPlanet.ServerStop';
	const CB_MP_BEGINMAP = 'ManiaPlanet.BeginMap';
	const CB_MP_BEGINMATCH = 'ManiaPlanet.BeginMatch';
	const CB_MP_ENDMATCH = 'ManiaPlanet.EndMatch';
	const CB_MP_ENDMAP = 'ManiaPlanet.EndMap';
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
	private $mControl = null;

	private $callbackHandlers = array();

	private $last1Second = -1;

	private $last5Second = -1;

	private $last1Minute = -1;

	private $last3Minute = -1;

	/**
	 * Construct callbacks handler
	 */
	public function __construct($mControl) {
		$this->mControl = $mControl;
		
		// Init values
		$this->last1Second = time();
		$this->last5Second = time();
		$this->last1Minute = time();
		$this->last3Minute = time();
	}

	/**
	 * Perform OnInit callback
	 */
	public function onInit() {
		// On init callback
		$this->triggerCallback(self::CB_IC_ONINIT, array(self::CB_IC_ONINIT));
		
		// Simulate begin map
		$map = $this->iControl->server->getMap();
		if ($map) {
			$this->triggerCallback(self::CB_IC_BEGINMAP, array(self::CB_IC_BEGINMAP, array($map)));
		}
	}

	/**
	 * Handles the given array of callbacks
	 */
	public function handleCallbacks() {
		// Perform ManiaControl callbacks
		if ($this->last1Second <= time() - 1) {
			$this->last1Second = time();
			
			// 1 second
			$this->triggerCallback(self::CB_IC_1_SECOND, array(self::CB_IC_1_SECOND));
			
			if ($this->last5Second <= time() - 5) {
				$this->last5Second = time();
				
				// 5 second
				$this->triggerCallback(self::CB_IC_5_SECOND, array(self::CB_IC_5_SECOND));
				
				if ($this->last1Minute <= time() - 60) {
					$this->last1Minute = time();
					
					// 1 minute
					$this->triggerCallback(self::CB_IC_1_MINUTE, array(self::CB_IC_1_MINUTE));
					
					if ($this->last3Minute <= time() - 180) {
						$this->last3Minute = time();
						
						// 3 minute
						$this->triggerCallback(self::CB_IC_3_MINUTE, array(self::CB_IC_3_MINUTE));
					}
				}
			}
		}
		
		// Get server callbacks
		if (!$this->iControl->client) return;
		$this->iControl->client->resetError();
		$this->iControl->client->readCB();
		$callbacks = $this->iControl->client->getCBResponses();
		if (!is_array($callbacks) || $this->iControl->client->isError()) {
			trigger_error("Error reading server callbacks. " . $this->iControl->getClientErrorText());
			return;
		}
		
		// Handle callbacks
		foreach ($callbacks as $index => $callback) {
			$callbackName = $callback[0];
			switch ($callbackName) {
				case self::CB_MP_BEGINMAP:
					{
						// Map begin
						$this->triggerCallback($callbackName, $callback);
						$this->triggerCallback(self::CB_IC_BEGINMAP, $callback);
						break;
					}
				case self::CB_MP_ENDMAP:
					{
						// Map end
						$this->triggerCallback($callbackName, $callback);
						$this->triggerCallback(self::CB_IC_ENDMAP, $callback);
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
	 * Trigger a specific callback
	 *
	 * @param string $callbackName        	
	 * @param mixed $data        	
	 */
	public function triggerCallback($callbackName, $data) {
		if (!array_key_exists($callbackName, $this->callbackHandlers) || !is_array($this->callbackHandlers[$callbackName])) return;
		foreach ($this->callbackHandlers[$callbackName] as $handler) {
			call_user_func(array($handler[0], $handler[1]), $data);
		}
	}

	/**
	 * Add a new callback handler
	 */
	public function registerCallbackHandler($callback, $handler, $method) {
		if (!is_object($handler) || !method_exists($handler, $method)) {
			trigger_error("Given handler can't handle callback '" . $callback . "' (no method '" . $method . "')!");
			return;
		}
		if (!array_key_exists($callback, $this->callbackHandlers) || !is_array($this->callbackHandlers[$callback])) {
			// Init callback handler array
			$this->callbackHandlers[$callback] = array();
		}
		// Register callback handler
		array_push($this->callbackHandlers[$callback], array($handler, $method));
	}
}

?>
