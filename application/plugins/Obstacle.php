<?php
use ManiaControl\ManiaControl;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\Plugin;

/**
 * ManiaControl Obstacle Plugin
 *
 * @author steeffeen
 */
class ObstaclePlugin implements CallbackListener, CommandListener, Plugin {
	/**
	 * Constants
	 */
	const ID = 7;
	const VERSION = 0.1;
	const CB_JUMPTO = 'Obstacle.JumpTo';
	const SCB_ONFINISH = 'OnFinish';
	const SCB_ONCHECKPOINT = 'OnCheckpoint';
	const SETTING_JUMPTOAUTHLEVEL = 'Authentication level for JumpTo commands';
	
	/**
	 * Private Properties
	 */
	/** @var maniaControl $maniaControl  */
	private $maniaControl = null;

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_JUMPTOAUTHLEVEL, 
				AuthenticationManager::AUTH_LEVEL_MODERATOR);
		
		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('jumpto', $this, 'command_JumpTo');
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerScriptCallbackListener(self::SCB_ONFINISH, $this, 'callback_OnFinish');
		$this->maniaControl->callbackManager->registerScriptCallbackListener(self::SCB_ONCHECKPOINT, $this, 'callback_OnCheckpoint');
		
		return true;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl->commandManager->unregisterCommandListener($this);
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
		return 'Obstacle Plugin';
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
		return "Plugin offering various Commands for the ShootMania 'Obstacle' Game Mode by steeffeen.";
	}

	/**
	 * Handle JumpTo command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 * @return bool
	 */
	public function command_JumpTo(array $chatCallback, Player $player) {
		$authLevel = $this->maniaControl->settingManager->getSetting($this, self::SETTING_JUMPTOAUTHLEVEL);
		if (!$this->maniaControl->authenticationManager->checkRight($player, $authLevel)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		// Send jump callback
		$params = explode(' ', $chatCallback[1][2], 2);
		$param = $player->login . ";" . $params[1] . ";";
		if (!$this->maniaControl->client->query('TriggerModeScriptEvent', self::CB_JUMPTO, $param)) {
			trigger_error("Couldn't send jump callback for '{$player->login}'. " . $this->maniaControl->getClientErrorText());
		}
	}

	/**
	 * Handle OnFinish script callback
	 *
	 * @param array $callback        	
	 */
	public function callback_OnFinish(array $callback) {
		$data = json_decode($callback[1]);
		$player = $this->maniaControl->playerManager->getPlayer($data->Player->Login);
		if (!$player) {
			return;
		}
		$time = $data->Run->Time;
		// Trigger trackmania player finish callback
		$finishCallback = array($player->pid, $player->login, $time);
		$finishCallback = array(CallbackManager::CB_TM_PLAYERFINISH, $finishCallback);
		$this->maniaControl->callbackManager->triggerCallback(CallbackManager::CB_TM_PLAYERFINISH, $finishCallback);
	}

	/**
	 * Handle OnCheckpoint script callback
	 *
	 * @param array $callback        	
	 */
	public function callback_OnCheckpoint(array $callback) {
		$data = json_decode($callback[1]);
		$player = $this->maniaControl->playerManager->getPlayer($data->Player->Login);
		if (!$player) {
			return;
		}
		$time = $data->Run->Time;
		// Trigger Trackmania player checkpoint callback
		// TODO: Checkpoint index (5th element)
		$checkpointCallback = array($player->pid, $player->login, $time, 0, 0);
		$checkpointCallback = array(CallbackManager::CB_TM_PLAYERCHECKPOINT, $checkpointCallback);
		$this->maniaControl->callbackManager->triggerCallback(CallbackManager::CB_TM_PLAYERCHECKPOINT, $checkpointCallback);
	}
}