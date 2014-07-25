<?php

namespace ManiaControl\Server;

use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Configurator\ConfiguratorMenu;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Structures\VoteRatio;

/**
 * Class offering a Configurator Menu for Vote Ratios
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class VoteRatiosMenu implements CallbackListener, ConfiguratorMenu, TimerListener {
	/*
	 * Constants
	 */
	const SETTING_PERMISSION_CHANGE_VOTE_RATIOS = 'Change Vote Ratios';
	const ACTION_PREFIX_VOTE_RATIO              = 'VoteRatiosMenu.VoteRatio.';

	/*
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Construct a new vote ratios menu instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Permissions
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_VOTE_RATIOS, AuthenticationManager::AUTH_LEVEL_ADMIN);
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getTitle()
	 */
	public static function getTitle() {
		return 'Vote Ratios';
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script, Player $player) {
		$voteRatioCommands = $this->getAllVoteRatioCommands();
		$voteRatios        = $this->maniaControl->client->getCallVoteRatios();

		$frame = new Frame();

		$posY       = $height * 0.41;
		$lineHeight = 5.;

		foreach ($voteRatioCommands as $index => $voteRatioCommand) {
			$voteRatioFrame = new Frame();
			$frame->add($voteRatioFrame);
			$voteRatioFrame->setY($posY);

			$nameLabel = new Label_Text();
			$voteRatioFrame->add($nameLabel);
			$nameLabel->setHAlign($nameLabel::LEFT)
			          ->setX($width * -0.46)
			          ->setSize($width * 0.7, $lineHeight)
			          ->setTextSize(2)
			          ->setText($voteRatioCommand);

			$entry = new Entry();
			$voteRatioFrame->add($entry);
			$entry->setX($width * 0.35)
			      ->setSize($width * 0.14, $lineHeight * 0.9)
			      ->setStyle(Label_Text::STYLE_TextValueSmall)
			      ->setTextSize($index === 0 ? 2 : 1)
			      ->setName(self::ACTION_PREFIX_VOTE_RATIO . $voteRatioCommand);

			$voteRatio = $this->getVoteRatioForCommand($voteRatios, $voteRatioCommand);
			if ($voteRatio) {
				$entry->setDefault($voteRatio->ratio);
			}

			$posY -= $lineHeight;
			if ($index === 0) {
				$posY -= $lineHeight;
			}
		}

		return $frame;
	}

	/**
	 * Return an array of all available vote ratio commands
	 *
	 * @return string[]
	 */
	private function getAllVoteRatioCommands() {
		return array('*', VoteRatio::COMMAND_RESTART_MAP, VoteRatio::COMMAND_NEXT_MAP, VoteRatio::COMMAND_SET_NEXT_MAP, VoteRatio::COMMAND_JUMP_MAP, VoteRatio::COMMAND_TEAM_BALANCE, VoteRatio::COMMAND_SCRIPT_SETTINGS, VoteRatio::COMMAND_KICK, VoteRatio::COMMAND_BAN);
	}

	/**
	 * Return the vote ratio for the given command
	 *
	 * @param VoteRatio[] $voteRatios
	 * @param string      $command
	 * @return VoteRatio
	 */
	private function getVoteRatioForCommand(array $voteRatios, $command) {
		foreach ($voteRatios as $voteRatio) {
			if ($voteRatio->command === $command) {
				return $voteRatio;
			}
		}
		return null;
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_VOTE_RATIOS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		if (!$configData[3] || strpos($configData[3][0]['Name'], self::ACTION_PREFIX_VOTE_RATIO) !== 0) {
			return;
		}
		$prefixLength = strlen(self::ACTION_PREFIX_VOTE_RATIO);

		$newVoteRatios = array();
		foreach ($configData[3] as $voteRatioEntry) {
			$voteRatioName  = substr($voteRatioEntry['Name'], $prefixLength);
			$voteRatioValue = $voteRatioEntry['Value'];
			if ($voteRatioValue === '') {
				continue;
			}
			if (!is_numeric($voteRatioValue)) {
				$this->sendInvalidValueError($player, $voteRatioName);
				continue;
			}

			$voteRatio          = new VoteRatio();
			$voteRatio->command = $voteRatioName;
			$voteRatio->ratio   = (float)$voteRatioValue;

			if (!$voteRatio->isValid()) {
				$this->sendInvalidValueError($player, $voteRatioName);
				continue;
			}
			array_push($newVoteRatios, $voteRatio);
		}

		$success = $this->maniaControl->client->setCallVoteRatios($newVoteRatios);
		if ($success) {
			$this->maniaControl->chat->sendSuccess('Vote Ratios saved!', $player);
		} else {
			$this->maniaControl->chat->sendError('Vote Ratios saving failed!', $player);
		}

		// Reopen the Menu
		$this->maniaControl->configurator->showMenu($player, $this);
	}

	/**
	 * Inform the player that his entered value is invalid
	 *
	 * @param Player $player
	 * @param string $commandName
	 */
	private function sendInvalidValueError(Player $player, $commandName) {
		$this->maniaControl->chat->sendError("Invalid Value given for '{$commandName}'!", $player);
	}
}
