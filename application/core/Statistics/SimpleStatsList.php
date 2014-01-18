<?php

namespace ManiaControl\Statistics;


use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;

class SimpleStatsList implements ManialinkPageAnswerListener{
	/**
	 * Constants
	 */
	const ACTION_OPEN_STATSLIST = 'SimpleStatsList.OpenStatsList';

	/**
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a PlayerList Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		//$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		//$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_OPENED, $this, 'handleWidgetOpened');
		//$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		// Action Open Playerlist
		//$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_STATSLIST, $this, 'command_playerList');
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Author);
		$itemQuad->setAction(self::ACTION_OPEN_STATSLIST);
		//$this->maniaControl->actionsMenu->addMenuItem($itemQuad, true, 14, 'Open Statistics');
	}



	/**
	 * Show the PlayerList Widget to the Player
	 *
	 * @param Player $player
	 */
	public function showStatsList(Player $player) {
		$width        = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height       = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();

		// Main frame
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition(0, 0, 10);

		// Background
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		// Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($width * 0.483, $height * 0.467, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		// Start offsets
		$x = -$width / 2;
		$y = $height / 2;

		// Predefine Description Label
		$descriptionLabel = new Label();
		$frame->add($descriptionLabel);
		$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
		$descriptionLabel->setPosition($x + 10, -$height / 2 + 5);
		$descriptionLabel->setSize($width * 0.7, 4);
		$descriptionLabel->setTextSize(2);
		$descriptionLabel->setVisible(false);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 5);

		$array = array("Id" => $x + 5, "Nickname" => $x + 18, "Login" => $x + 70, "Location" => $x + 101);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		// get PlayerList
		$players = $this->maniaControl->playerManager->getPlayers();

		$i = 1;
		$y -= 10;
		foreach($players as $listPlayer) {
			/**
			 *
			 * @var Player $listPlayer
			 */

			$path        = $listPlayer->getProvince();
			$playerFrame = new Frame();
			$frame->add($playerFrame);

			if($i % 2 != 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			$array = array($i => $x + 5, $listPlayer->nickname => $x + 18, $listPlayer->login => $x + 70, $path => $x + 101);
			$this->maniaControl->manialinkManager->labelLine($playerFrame, $array);

			$playerFrame->setY($y);


			$i++;
			$y -= 4;
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'PlayerList');
	}
} 