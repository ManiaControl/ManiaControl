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
 * ManiaControl Help Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2015 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */

class HelpManager implements CommandListener, CallbackListener {
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $playerCommands = array();
	private $adminCommands = array();

	/**
	 * Construct a new Commands Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'handleOnInit');
	}

	/**
	 * Handle ManiaControl OnInit Callback
	 */
	public function handleOnInit() {
		$this->maniaControl->getCommandManager()->registerCommandListener('help', $this, 'command_playerHelp', false, 'Shows all commands in chat.');
		$this->maniaControl->getCommandManager()->registerCommandListener('helpall', $this, 'command_playerHelpAll', false, 'Shows all commands in ManiaLink with description.');
		$this->maniaControl->getCommandManager()->registerCommandListener('help', $this, 'command_adminHelp', true, 'Shows all admin commands in chat.');
		$this->maniaControl->getCommandManager()->registerCommandListener('helpall', $this, 'command_adminHelpAll', true, 'Shows all admin commands in ManiaLink with description.');
	}

	/**
	 * Show a list of Admin Commands
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_adminHelp(array $chatCallback, Player $player) {
        // Parse list from array
        $message = $this->parseHelpList($this->adminCommands);
        
        // Show message when it's not empty
        if($message != NULL){
            $message = 'Supported Admin Commands: ' . $message;
            $this->maniaControl->getChat()->sendChat($message, $player);
        }
	}

	/**
	 * Show a list of Player Commands
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_playerHelp(array $chatCallback, Player $player) {
        // Parse list from array
		$message = $this->parseHelpList($this->playerCommands);
        
        // Show message when it's not empty
        if($message != NULL){
            $message = 'Supported Player Commands: ' . $message;
            $this->maniaControl->getChat()->sendChat($message, $player);
        }
	}

	/**
	 * Show a ManiaLink list of Player Commands
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_playerHelpAll(array $chatCallback, Player $player) {
        $this->parseHelpList($this->playerCommands, true, $player);
	}

	/**
     * Parse list with commands from array
     * @param array     $commands
     * @param bool      $isHelpAll
     * @param Player    $player
     * @return string|void
     */
	private function parseHelpList(array $commands, $isHelpAll = false, Player $player = null) {
		$showCommands      = array();
		$registeredMethods = array();
        $message           = '';
		foreach (array_reverse($commands) as $command) {
			if (array_key_exists($command['Method'], $registeredMethods)) {
				if($showCommands[$registeredMethods[$command['Method']]]['Description'] === $command['Description']) {
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

		usort($showCommands, function ($commandA, $commandB) {
			return strcmp($commandA['Name'], $commandB['Name']);
		});

		if(!$isHelpAll){
			foreach ($showCommands as $command) {
				$message .= $command['Name'] . ',';
			}
			$message = substr($message, 0, -1);

			return $message;
		}else{
			if($player != NULL){
				$this->showHelpAllList($showCommands, $player);
			}
		}
        
        return;
	}

	/**
	 * Show the HelpAll list to the player.
	 *
	 * @param array $commands
	 * @param mixed $player
	 */
	private function showHelpAllList(array $commands, $player) {
		$width  = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();

		// create manialink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);

		// Main frame
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame($script, $paging);
		$maniaLink->add($frame);

		// Start offsets
		$posX = -$width / 2;
		$posY = $height / 2;

		//Predefine description Label
		$descriptionLabel = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($posY - 5);
		$array = array('Command' => $posX + 5, 'Description' => $posX + 50);
		$this->maniaControl->getManialinkManager()->labelLine($headFrame, $array);

		$index = 1;
		$posY -= 10;
		$pageFrame = null;

		foreach ($commands as $command) {
			if ($index % 15 === 1) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$posY = $height / 2 - 10;
				$paging->addPage($pageFrame);
			}

			$playerFrame = new Frame();
			$pageFrame->add($playerFrame);
			$playerFrame->setY($posY);

			if ($index % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			$array  = array($command['Name'] => $posX + 5, $command['Description'] => $posX + 50);
			$labels = $this->maniaControl->getManialinkManager()->labelLine($playerFrame, $array);

			$label = $labels[0];
			$label->setWidth(40);

			$posY -= 4;
			$index++;
		}

		// Render and display xml
		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $player, 'HelpAllList');
	}

	/**
	 * Show a ManiaLink list of Admin Commands
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_adminHelpAll(array $chatCallback, Player $player) {
        $this->parseHelpList($this->adminCommands, true, $player);
	}

	/**
	 * Register a new Command
	 *
	 * @param string $name
	 * @param bool   $adminCommand
	 * @param string $description
	 * @param string $method
	 */
	public function registerCommand($name, $adminCommand = false, $description = '', $method) {
		if ($adminCommand) {
			array_push($this->adminCommands, array("Name" => $name, "Description" => $description, "Method" => $method));
		} else {
			array_push($this->playerCommands, array("Name" => $name, "Description" => $description, "Method" => $method));
		}
	}
} 
