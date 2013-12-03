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
class ObstaclePlugin extends Plugin implements CallbackListener, CommandListener {
	/**
	 * Constants
	 */
	const VERSION = '1.0';
	const CB_JUMPTO = 'Obstacle.JumpTo';
	const SCB_ONFINISH = 'OnFinish';
	const SCB_ONCHECKPOINT = 'OnCheckpoint';
	const SETTING_JUMPTOAUTHLEVEL = 'Authentication level for JumpTo commands';

	/**
	 * Create new obstacle plugin
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Plugin details
		$this->name = 'Obstacle Plugin';
		$this->version = self::VERSION;
		$this->author = 'steeffeen';
		$this->description = 'Plugin offering various Commands for the ShootMania Obstacle Game Mode.';
		
		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_JUMPTOAUTHLEVEL, 
				AuthenticationManager::AUTH_LEVEL_OPERATOR);
		
		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('jumpto', $this, 'command_JumpTo');
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerScriptCallbackListener(self::SCB_ONFINISH, $this, 'callback_OnFinish');
		$this->maniaControl->callbackManager->registerScriptCallbackListener(self::SCB_ONCHECKPOINT, $this, 'callback_OnCheckpoint');
	}

	/**
	 * Handle JumpTo command
	 *
	 * @param array $chatCallback        	
	 * @return bool
	 */
	public function command_JumpTo(array $chatCallback, Player $player) {
		$authLevel = $this->maniaControl->settingManager->getSetting($this, self::SETTING_JUMPTOAUTHLEVEL);
		if (!$this->maniaControl->authenticationManager->checkRight($player, $authLevel)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		// Send jump callback
		$params = explode(' ', $chatCallback[1][2], 2);
		$param = $player->login . ";" . $params[1] . ";";
		if (!$this->maniaControl->client->query('TriggerModeScriptEvent', self::CB_JUMPTO, $param)) {
			trigger_error("Couldn't send jump callback for '{$player->login}'. " . $this->maniaControl->getClientErrorText());
		}
		return true;
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
		// Trigger trackmania player finish callback
		$finishCallback = array($player->pid, $player->login, $time);
		$finishCallback = array(CallbackManager::CB_TM_PLAYERCHECKPOINT, $finishCallback);
		$this->maniaControl->callbackManager->triggerCallback(CallbackManager::CB_TM_PLAYERCHECKPOINT, $finishCallback);
	}
}

?>
