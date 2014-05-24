<?php

namespace steeffeen;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\Plugin;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * ManiaControl Obstacle Plugin
 *
 * @author steeffeen
 */
class ObstaclePlugin implements CallbackListener, CommandListener, Plugin {
	/*
	 * Constants
	 */
	const ID                       = 24;
	const VERSION                  = 0.2;
	const NAME                     = 'Obstacle Plugin';
	const AUTHOR                   = 'steeffeen';
	const CB_JUMPTO                = 'Obstacle.JumpTo';
	const SCB_ONFINISH             = 'OnFinish';
	const SCB_ONCHECKPOINT         = 'OnCheckpoint';
	const SETTING_JUMPTO_AUTHLEVEL = 'Authentication level for JumpTo commands';

	/**
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return self::NAME;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return self::AUTHOR;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return "Plugin offering CP Jumping and Local Records Support for the ShootMania GameMode 'Obstacle'.";
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_JUMPTO_AUTHLEVEL, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('jumpto', $this, 'command_JumpTo', true);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerScriptCallbackListener(self::SCB_ONFINISH, $this, 'callback_OnFinish');
		$this->maniaControl->callbackManager->registerScriptCallbackListener(self::SCB_ONCHECKPOINT, $this, 'callback_OnCheckpoint');

		return true;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
	}

	/**
	 * Handle JumpTo command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 * @return bool
	 */
	public function command_JumpTo(array $chatCallback, Player $player) {
		$authLevel = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_JUMPTO_AUTHLEVEL);
		if (!$this->maniaControl->authenticationManager->checkRight($player, $authLevel)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		// Send jump callback
		$params = explode(' ', $chatCallback[1][2], 2);
		if (count($params) < 2) {
			$message = "Usage: '//jumpto login' or '//jumpto checkpointnumber'";
			$this->maniaControl->chat->sendUsageInfo($message, $player);
			return;
		}

		$param = $player->login . ";" . $params[1] . ";";
		try {
			$this->maniaControl->client->triggerModeScriptEvent(self::CB_JUMPTO, $param);
		} catch (GameModeException $e) {
		}
	}

	/**
	 * Handle OnFinish script callback
	 *
	 * @param array $callback
	 */
	public function callback_OnFinish(array $callback) {
		$data   = json_decode($callback[1]);
		$player = $this->maniaControl->playerManager->getPlayer($data->Player->Login);
		if (!$player) {
			return;
		}

		// Trigger finish callback
		$finishCallback              = new RecordCallback();
		$finishCallback->rawCallback = $callback;
		$finishCallback->name        = $finishCallback::FINISH;
		$finishCallback->setPlayer($player);
		$finishCallback->time = $data->Run->Time;

		$this->maniaControl->callbackManager->triggerCallback($finishCallback);
	}

	/**
	 * Handle OnCheckpoint script callback
	 *
	 * @param array $callback
	 */
	public function callback_OnCheckpoint(array $callback) {
		$data   = json_decode($callback[1]);
		$player = $this->maniaControl->playerManager->getPlayer($data->Player->Login);
		if (!$player) {
			return;
		}

		// Trigger checkpoint callback
		$checkpointCallback              = new RecordCallback();
		$checkpointCallback->rawCallback = $callback;
		$checkpointCallback->name        = $checkpointCallback::CHECKPOINT;
		$checkpointCallback->setPlayer($player);
		$checkpointCallback->time = $data->Run->Time;

		$this->maniaControl->callbackManager->triggerCallback($checkpointCallback);
	}
}
