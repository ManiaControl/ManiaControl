<?php
use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Plugins\Plugin;

/**
 * Plugin for the TM Game Mode 'Endurance' by TGYoshi
 *
 * @author steeffeen
 */
class EndurancePlugin implements CallbackListener, Plugin {
	/**
	 * Constants
	 */
	const ID = 4;
	const VERSION = 0.1;
	const CB_CHECKPOINT = 'Endurance.Checkpoint';
	
	/**
	 * Private properties
	 */
	private $currentMap = null;
	private $playerLapTimes = array();

	/**
	 * Create a new endurance plugin instance
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'callback_OnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this, 'callback_BeginMap');
		$this->maniaControl->callbackManager->registerScriptCallbackListener(self::CB_CHECKPOINT, $this, 'callback_Checkpoint');
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
	 * @param array $callback        	
	 */
	public function callback_BeginMap(array $callback) {
		$this->currentMap = $this->maniaControl->mapManager->getCurrentMap();
		$this->playerLapTimes = array();
	}

	/**
	 * Handle Endurance Checkpoint callback
	 *
	 * @param array $callback        	
	 */
	public function callback_Checkpoint(array $callback) {
		$callbackData = json_decode($callback[1]);
		if ($callbackData->Checkpoint % $this->currentMap->nbCheckpoints != 0) {
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
