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
 * @copyright 2014-2020 ManiaControl Team
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
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct a new vote ratios menu instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Permissions
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_VOTE_RATIOS, AuthenticationManager::AUTH_LEVEL_ADMIN);
	}

	/**
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::getTitle()
	 */
	public static function getTitle() {
		return 'Vote Ratios';
	}

	/**
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script, Player $player) {
		$frame      = new Frame();
		$posY       = $height * 0.41;
		$lineHeight = 5.;
		$index      = 0;

		$voteRatioCommands = $this->getVoteCommands();
		$voteRatios        = $this->maniaControl->getClient()->getCallVoteRatios();
		foreach ($voteRatioCommands as $voteRatioCommand => $voteRatioDescription) {
			$voteRatioFrame = new Frame();
			$frame->addChild($voteRatioFrame);
			$voteRatioFrame->setY($posY);

			$nameLabel = new Label_Text();
			$voteRatioFrame->addChild($nameLabel);
			$nameLabel->setHorizontalAlign($nameLabel::LEFT)->setX($width * -0.46)->setSize($width * 0.7, $lineHeight)->setTextSize(2)->setTranslate(true)->setText($voteRatioDescription);

			$entry = new Entry();
			$voteRatioFrame->addChild($entry);
			$entry->setX($width * 0.35)->setSize($width * 0.14, $lineHeight * 0.9)->setStyle(Label_Text::STYLE_TextValueSmall)->setTextSize($index === 0 ? 2 : 1)->setName(self::ACTION_PREFIX_VOTE_RATIO . $voteRatioCommand);

			$voteRatio = $this->getVoteRatioForCommand($voteRatios, $voteRatioCommand);
			if ($voteRatio) {
				$entry->setDefault($voteRatio->ratio);
			}

			$posY -= $lineHeight;
			if ($index === 0) {
				$posY -= $lineHeight;
			}
			$index++;
		}

		return $frame;
	}

	/**
	 * Get the list of available vote commands
	 *
	 * @return string[]
	 */
	private function getVoteCommands() {
		return array(VoteRatio::COMMAND_DEFAULT => 'Default', VoteRatio::COMMAND_RESTART_MAP => 'Restart Map', VoteRatio::COMMAND_NEXT_MAP => 'Skip Map', VoteRatio::COMMAND_SET_NEXT_MAP => 'Set next Map', VoteRatio::COMMAND_JUMP_MAP => 'Jump to Map', VoteRatio::COMMAND_TEAM_BALANCE => 'Balance Teams', VoteRatio::COMMAND_SCRIPT_SETTINGS => 'Change Script Settings and Commands', VoteRatio::COMMAND_KICK => 'Kick Players', VoteRatio::COMMAND_BAN => 'Ban Players');
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
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CHANGE_VOTE_RATIOS)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
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

		$success = $this->maniaControl->getClient()->setCallVoteRatios($newVoteRatios);
		if ($success) {
			$this->maniaControl->getChat()->sendSuccess('Vote Ratios saved!', $player);
		} else {
			$this->maniaControl->getChat()->sendError('Vote Ratios saving failed!', $player);
		}

		// Reopen the Menu
		$this->maniaControl->getConfigurator()->showMenu($player, $this);
	}

	/**
	 * Inform the player that his entered value is invalid
	 *
	 * @param Player $player
	 * @param string $commandName
	 */
	private function sendInvalidValueError(Player $player, $commandName) {
		$message = $this->maniaControl->getChat()->formatMessage(
			'Invalid Value given for %s!',
			$commandName
		);
		$this->maniaControl->getChat()->sendError($message, $player);
	}
}
