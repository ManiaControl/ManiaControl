<?php

namespace ManiaControl\Manialinks;

use FML\CustomUI;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Class managing the Custom UI Settings
 *
 * @author steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CustomUIManager implements CallbackListener, TimerListener {
	/*
	 * Constants
	 */
	const CUSTOMUI_MLID = 'CustomUI.MLID';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	/** @var customUI $customUI */
	private $customUI = null;
	private $updateManialink = false;

	/**
	 * Create a Custom UI Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->prepareManialink();

		// Register for callbacks
		$this->maniaControl->timerManager->registerTimerListening($this, 'handle1Second', 1000);
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerJoined');
	}

	/**
	 * Create the ManiaLink and CustomUI instances
	 */
	private function prepareManialink() {
		$this->customUI = new CustomUI();
	}

	/**
	 * Update the CustomUI Manialink
	 *
	 * @param Player $player
	 */
	private function updateManialink(Player $player = null) {
		if ($player) {
			$this->maniaControl->manialinkManager->sendManialink($this->customUI, $player->login);
			return;
		}
		$this->maniaControl->manialinkManager->sendManialink($this->customUI);
	}

	/**
	 * Handle 1Second
	 *
	 * @param $time
	 */
	public function handle1Second($time) {
		if (!$this->updateManialink) {
			return;
		}
		$this->updateManialink = false;
		$this->updateManialink();
	}

	/**
	 * Handle PlayerJoined Callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerJoined(Player $player) {
		$this->updateManialink($player);

		//send it again after 500ms
		$this->maniaControl->timerManager->registerOneTimeListening($this, function($time) use (&$player){
			$this->updateManialink($player);
		},500);
	}

	/**
	 * Set Showing of Notices
	 *
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
