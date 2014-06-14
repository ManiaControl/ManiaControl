<?php

namespace ManiaControl\Commands;

use FML\Controls\Frame;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Players\Player;

/**
 * Help Manager
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class HelpManager implements CommandListener, CallbackListener {
	/*
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
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::ONINIT, $this, 'handleOnInit');
	}

	/**
	 * Handle ManiaControl OnInit Callback
	 */
	public function handleOnInit() {
		//Register the help command
		$this->maniaControl->commandManager->registerCommandListener('help', $this, 'command_playerHelp', false, 'Shows all commands in chat.');
		$this->maniaControl->commandManager->registerCommandListener('helpall', $this, 'command_playerHelpAll', false, 'Shows all commands in ManiaLink with description.');
		$this->maniaControl->commandManager->registerCommandListener('help', $this, 'command_adminHelp', true, 'Shows all admin commands in chat.');
		$this->maniaControl->commandManager->registerCommandListener('helpall', $this, 'command_adminHelpAll', true, 'Shows all admin commands in ManiaLink with description.');
	}

	/**
	 * Shows a list of Admin Commands
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_adminHelp(array $chatCallback, Player $player) {
		$showCommands      = array();
		$registeredMethods = array();
		foreach (array_reverse($this->adminCommands) as $command) {
			if (array_key_exists($command['Method'], $registeredMethods) && $showCommands[$registeredMethods[$command['Method']]]['Description'] === $command['Description']) {
				$name = $registeredMethods[$command['Method']];
				$showCommands[$name]['Name'] .= '|' . $command['Name'];
			} else {
				$showCommands[$command['Name']]        = $command;
				$registeredMethods[$command['Method']] = $command['Name'];
			}
		}

		usort($showCommands, function ($a, $b) {
			return strcmp($a["Name"], $b["Name"]);
		});

		$message = 'Supported Admin Commands: ';
		foreach ($showCommands as $command) {
			$message .= $command['Name'] . ',';
		}
		$message = substr($message, 0, -1);
		$this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Shows a list of Player Commands
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_playerHelp(array $chatCallback, Player $player) {
		$showCommands      = array();
		$registeredMethods = array();
		foreach (array_reverse($this->playerCommands) as $command) {
			if (array_key_exists($command['Method'], $registeredMethods) && $showCommands[$registeredMethods[$command['Method']]]['Description'] === $command['Description']) {
				$name = $registeredMethods[$command['Method']];
				$showCommands[$name]['Name'] .= '|' . $command['Name'];
			} else {
				$showCommands[$command['Name']]        = $command;
				$registeredMethods[$command['Method']] = $command['Name'];
			}
		}

		usort($showCommands, function ($a, $b) {
			return strcmp($a["Name"], $b["Name"]);
		});

		$message = 'Supported Player Commands: ';
		foreach ($showCommands as $command) {
			$message .= $command['Name'] . ',';
		}
		$message = substr($message, 0, -1);
		$this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Shows a ManiaLink list of Player Commands
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_playerHelpAll(array $chatCallback, Player $player) {
		$this->prepareHelpAll($this->playerCommands, $player);
	}

	/**
	 * Prepare the commands for the HelpAll ManiaLink.
	 *
	 * @param array $commands
	 * @param mixed $player
	 */
	private function prepareHelpAll(array $commands, $player) {
		$showCommands      = array();
		$registeredMethods = array();
		foreach (array_reverse($commands) as $command) {
			if (array_key_exists($command['Method'], $registeredMethods)) {
				if ($showCommands[$registeredMethods[$command['Method']]]['Description'] === $command['Description']) {
					$name = $registeredMethods[$command['Method']];
					$showCommands[$name]['Name'] .= '|' . $command['Name'];
				} else {
					$showCommands[$command['Name']]        = $command;
					$registeredMethods[$command['Method']] = $command['Name'];
				}
			} else {
				$showCommands[$command['Name']]        = $command;
				$registeredMethods[$command['Method']] = $command['Name'];
			}
		}

		usort($showCommands, function ($a, $b) {
			return strcmp($a["Name"], $b["Name"]);
		});

		$this->showHelpAllList($showCommands, $player);
	}

	/**
	 * Shows the HelpAll list to the player.
	 *
	 * @param array $commands
	 * @param mixed $player
	 */
	private function showHelpAllList(array $commands, $player) {
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

		// create manialink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $paging);
		$maniaLink->add($frame);

		// Start offsets
		$x = -$width / 2;
		$y = $height / 2;

		//Predefine description Label
		$descriptionLabel = $this->maniaControl->manialinkManager->styleManager->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 5);
		$array = array("Command" => $x + 5, "Description" => $x + 50);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$i         = 1;
		$y         = $y - 10;
		$pageFrame = null;

		foreach ($commands as $command) {
			if ($i % 15 === 1) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$y = $height / 2 - 10;
				$paging->addPage($pageFrame);
			}

			$playerFrame = new Frame();
			$pageFrame->add($playerFrame);
			$playerFrame->setY($y);

			if ($i % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			$array  = array($command['Name'] => $x + 5, $command['Description'] => $x + 50);
			$labels = $this->maniaControl->manialinkManager->labelLine($playerFrame, $array);

			$label = $labels[0];
			$label->setWidth(40);

			$y -= 4;
			$i++;
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'HelpAllList');
	}

	/**
	 * Shows a ManiaLink list of Admin Commands
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_adminHelpAll(array $chatCallback, Player $player) {
		$this->prepareHelpAll($this->adminCommands, $player);
	}

	/**
	 * Registers a new Command
	 *
	 * @param        $name
	 * @param bool   $adminCommand
	 * @param string $description
	 * @param        $method
	 */
	public function registerCommand($name, $adminCommand = false, $description = '', $method) {
		if ($adminCommand) {
			array_push($this->adminCommands, array("Name" => $name, "Description" => $description, "Method" => $method));
		} else {
			array_push($this->playerCommands, array("Name" => $name, "Description" => $description, "Method" => $method));
		}
	}
} 
