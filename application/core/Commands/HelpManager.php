<?php

namespace ManiaControl\Commands;


use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

class HelpManager implements CommandListener {
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
	public function __construct(ManiaControl $maniaControl, CommandManager $commandManager) {
		$this->maniaControl = $maniaControl;

		//Register the help command
		$commandManager->registerCommandListener('help', $this, 'command_playerHelp', false);
	}


	public function command_playerHelp(array $chat, Player $player) {
		$string = '';

		foreach($this->playerCommands as $key => $value) {
			var_dump($key, $value);
		}
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