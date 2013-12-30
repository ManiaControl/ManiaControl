<?php

namespace ManiaControl\Manialinks;

use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use FML\ManiaLink;
use FML\Elements\CustomUI;
use FML\Controls\Quad;
use FML\ManiaLinks;

/**
 * Class managing the Custom UI Settings
 *
 * @author steeffeen & kremsy
 */
class CustomUIManager implements CallbackListener {
	
	/**
	 * Constants
	 */
	const CUSTOMUI_MLID = 'CustomUI.MLID';
	
	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $manialinks = null;
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
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_1_SECOND, $this, 'handle1Second');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerJoined');
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
		// TODO: improve rendering after FML update
		$domDocument = new \DOMDocument();
		$element = $this->customUI->render($domDocument);
		$domDocument->appendChild($element);
		$manialinkText = $domDocument->saveXML();
		if ($player) {
			$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
			return;
		}
		$this->maniaControl->manialinkManager->sendManialink($manialinkText);
	}

	/**
	 * Handle 1Second Callback
	 *
	 * @param array $callback        	
	 */
	public function handle1Second(array $callback) {
		if (!$this->updateManialink) return;
		$this->updateManialink = false;
		$this->updateManialink();
	}

	/**
	 * Handle PlayerJoined Callback
	 *
	 * @param array $callback        	
	 */
	public function handlePlayerJoined(array $callback) {
		$player = $callback[1];
		$this->updateManialink($player);
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
