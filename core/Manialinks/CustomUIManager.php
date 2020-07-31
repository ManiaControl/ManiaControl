<?php

namespace ManiaControl\Manialinks;

use FML\CustomUI;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Class managing the Custom UI in ManiaPlanet
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CustomUIManager implements CallbackListener, TimerListener, UsageInformationAble, CommandListener {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const CUSTOMUI_MLID = 'CustomUI.MLID';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var customUI $customUI */
	private $customUI        = null;
	private $updateManialink = false;

	/**
	 * Create a custom UI manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->prepareManialink();

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerJoined');
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handle1Second', 1000);

	}


	/**
	 * Create the ManiaLink and CustomUI instances
	 */
	private function prepareManialink() {
		$this->customUI = new CustomUI();
	}

	/**
	 * Handle 1 Second Callback
	 */
	public function handle1Second() {
		if (!$this->updateManialink) {
			return;
		}
		$this->updateManialink = false;
		$this->updateManialink();
	}

	/**
	 * Update the CustomUI Manialink
	 *
	 * @param Player $player
	 */
	public function updateManialink(Player $player = null) {
		$this->maniaControl->getManialinkManager()->sendManialink($this->customUI, $player);
	}

	/**
	 * Handle PlayerJoined Callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerJoined(Player $player) {
		$this->updateManialink($player);

		//TODO: validate necessity
		//send it again after 500ms
		$this->maniaControl->getTimerManager()->registerOneTimeListening($this, function () use (&$player) {
			$this->updateManialink($player);
		}, 500);
	}

	/**
	 * Set Showing of Notices
	 *
	 * @see \ManiaControl\Manialinks\CustomUIManager::enableNotices()
	 * @deprecated
	 * @param bool $visible
	 */
	public function setNoticeVisible($visible) {
		$this->customUI->setNoticeVisible($visible);
		$this->updateManialink = true;
	}

	/**
	 * Set Showing of the Challenge Info
	 *
	 * @param bool $visible
	 */
	public function setChallengeInfoVisible($visible) {
		$this->customUI->setChallengeInfoVisible($visible);
		$this->updateManialink = true;
	}

	/**
	 * Set Showing of the Net Infos
	 *
	 * @param bool $visible
	 */
	public function setNetInfosVisible($visible) {
		$this->customUI->setNetInfosVisible($visible);
		$this->updateManialink = true;
	}

	/**
	 * Set Showing of the Chat
	 *
	 * @param bool $visible
	 */
	public function setChatVisible($visible) {
		$this->customUI->setChatVisible($visible);
		$this->updateManialink = true;
	}

	/**
	 * Set Showing of the Checkpoint List
	 *
	 * @param bool $visible
	 */
	public function setCheckpointListVisible($visible) {
		$this->customUI->setCheckpointListVisible($visible);
		$this->updateManialink = true;
	}

	/**
	 * Set Showing of Round Scores
	 *
	 * @param bool $visible
	 */
	public function setRoundScoresVisible($visible) {
		$this->customUI->setRoundScoresVisible($visible);
		$this->updateManialink = true;
	}

	/**
	 * Set Showing of the Scoretable
	 *
	 * @param bool $visible
	 */
	public function setScoretableVisible($visible) {
		$this->customUI->setScoretableVisible($visible);
		$this->updateManialink = true;
	}

	/**
	 * Set Global Showing
	 *
	 * @param bool $visible
	 */
	public function setGlobalVisible($visible) {
		$this->customUI->setGlobalVisible($visible);
		$this->updateManialink = true;
	}

}
