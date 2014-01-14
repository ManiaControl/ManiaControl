<?php

namespace ManiaControl\Commands;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * ManiaControl Chat-Message Plugin
 *
 * @author kremsy and steeffeen
 */
class HelpManager implements CommandListener, CallbackListener {
	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $playerCommands = array();
	private $adminCommands = array();

	/**
	 * Construct a new Commands Manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');
	}

	public function handleOnInit(array $callback) {
		//Register the help command
		$this->maniaControl->commandManager->registerCommandListener('help', $this, 'command_playerHelp', false);
		$this->maniaControl->commandManager->registerCommandListener('help', $this, 'command_adminHelp', true);
	}

	/**
	 * Shows a list of Admin Commands
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_adminHelp(array $chat, Player $player) {
		//TODO only show first command in command arrays
		$message = '$sSupported Admin Commands: ';
		foreach(array_reverse($this->adminCommands) as $command) {
			$message .= $command['Name'] . ',';
		}
		$message = substr($message, 0, -1);
		$this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Shows a list of Player Commands
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_playerHelp(array $chat, Player $player) {
		//TODO only show first command in command arrays
		$message = '$sSupported Player Commands: ';
		foreach(array_reverse($this->playerCommands) as $command) {
			$message .= $command['Name'] . ',';
		}
		$message = substr($message, 0, -1);
		$this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Registers a new Command
	 *
	 * @param $name
	 * @param $adminCommand
	 * @param $description
	 */
	public function registerCommand($name, $adminCommand = false, $description = '') {
		if($adminCommand) {
			array_push($this->adminCommands, array("Name" => $name, "Description" => $description));
		} else {
			array_push($this->playerCommands, array("Name" => $name, "Description" => $description));
		}
	}

} 