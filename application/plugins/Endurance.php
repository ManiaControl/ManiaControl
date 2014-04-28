<?php

namespace steeffeen;

use ManiaControl\Callbacks\Callbacks;
use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Maps\Map;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Maps\MapManager;

/**
 * Plugin for the TM Game Mode 'Endurance' by TGYoshi
 *
 * @author steeffeen
 */
class EndurancePlugin implements CallbackListener, Plugin {
	/**
	 * Constants
	 */
	const ID = 25;
	const VERSION = 0.1;
	const CB_CHECKPOINT = 'Endurance.Checkpoint';
	
	/**
	 * Private properties
	 */
	/** @var maniaControl $maniaControl  */
	private $maniaControl = null;
	/** @var Map $currentMap */
	private $currentMap = null;
	private $playerLapTimes = array();

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		//do nothing
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_ONINIT, $this, 'callback_OnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::BEGINMAP, $this, 'callback_BeginMap');
		$this->maniaControl->callbackManager->registerScriptCallbackListener(self::CB_CHECKPOINT, $this, 'callback_Checkpoint');
		
		return true;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
		$this->maniaControl->callbackManager->unregisterScriptCallbackListener($this);
		unset($this->maniaControl);
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return 'Endurance Plugin';
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return 'steeffeen';
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return "Plugin enabling Support for the TM Game Mode 'Endurance' by TGYoshi.";
	}

	/**
	 * Handle ManiaControl OnInit callback
	 *
	 * @param array $callback        	
	 */
	public function callback_OnInit(array $callback) {
		$this->currentMap = $this->maniaControl->mapManager->getCurrentMap();
		$this->playerLapTimes = array();
	}

	/**
	 * Handle BeginMap callback
	 *
	 * @param Map $map
	 */
	public function callback_BeginMap(Map $map) {
		$this->currentMap = $map;
		$this->playerLapTimes = array();
	}

	/**
	 * Handle Endurance Checkpoint callback
	 *
	 * @param array $callback        	
	 */
	public function callback_Checkpoint(array $callback) {
		$callbackData = json_decode($callback[1]);
		if (!$this->currentMap->nbCheckpoints || $callbackData->Checkpoint % $this->currentMap->nbCheckpoints != 0) {
			return;
		}
		$player = $this->maniaControl->playerManager->getPlayer($callbackData->Login);
		if (!$player) {
			return;
		}
		$time = $callbackData->Time;
		if ($time <= 0) {
			return;
		}
		if (isset($this->playerLapTimes[$player->login])) {
			$time -= $this->playerLapTimes[$player->login];
		}
		$this->playerLapTimes[$player->login] = $callbackData->Time;
		// Trigger trackmania player finish callback
		$finishCallback = array($player->pid, $player->login, $time);
		$finishCallback = array(CallbackManager::CB_TM_PLAYERFINISH, $finishCallback);
		$this->maniaControl->callbackManager->triggerCallback(CallbackManager::CB_TM_PLAYERFINISH, $finishCallback);
	}
}
