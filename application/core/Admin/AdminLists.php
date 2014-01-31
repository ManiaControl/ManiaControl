<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 31.01.14
 * Time: 12:54
 */

namespace ManiaControl\Admin;


use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;

class AdminLists implements ManialinkPageAnswerListener {
	/**
	 * Constants
	 */
	const ACTION_OPEN_ADMINLISTS = "AdminList.OpenAdminLists";
	const MAX_PLAYERS_PER_PAGE   = 15;

	/**
	 * Create a PlayerList Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		/*$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLOSE_PLAYER_ADV, $this, 'closePlayerAdvancedWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_OPENED, $this, 'handleWidgetOpened');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		// Update Widget Events
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERINFOCHANGED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECTED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(AuthenticationManager::CB_AUTH_LEVEL_CHANGED, $this, 'updateWidget');*/

		// Action Open Playerlist
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_ADMINLISTS, $this, 'openAdminList');
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Author);
		$itemQuad->setAction(self::ACTION_OPEN_ADMINLISTS);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, false, 14, 'Open Adminlist');
	}

	public function openAdminList(array $callback, Player $player) {
		$this->showAdminLists($player);
	}

	public function showAdminLists(Player $player) {
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

		// get PlayerList
		//	$admins = $this->maniaControl->authenticationManager->
		/*$players = $this->maniaControl->playerManager->getPlayers();
		$pagesId = '';
		if (count($players) > self::MAX_PLAYERS_PER_PAGE) {
			$pagesId = 'AdminListsPages';
		}*/
		$pagesId = 'AdminListsPages';


		//create manialink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $pagesId);
		$maniaLink->add($frame);

		// Start offsets
		$x = -$width / 2;
		//Predefine description Label
		$descriptionLabel = $this->maniaControl->manialinkManager->styleManager->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);

		$admins = $this->maniaControl->authenticationManager->getAdmins();

		$i          = 1;
		$y          = $height / 2 - 10;
		$pageFrames = array();
		foreach($admins as $admin) {
			if (!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				if (!empty($pageFrames)) {
					$pageFrame->setVisible(false);
				}
				array_push($pageFrames, $pageFrame);
				$y = $height / 2 - 10;
				$script->addPage($pageFrame, count($pageFrames), $pagesId);
			}


			$playerFrame = new Frame();
			$pageFrame->add($playerFrame);
			$playerFrame->setY($y);

			if ($i % 2 != 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			$array = array($i => $x + 5, $admin->nickname => $x + 18, $admin->login => $x + 70);
			$this->maniaControl->manialinkManager->labelLine($playerFrame, $array);


			// Level Quad
			$rightQuad = new Quad_BgRaceScore2();
			$playerFrame->add($rightQuad);
			$rightQuad->setX($x + 13);
			$rightQuad->setZ(5);
			$rightQuad->setSubStyle($rightQuad::SUBSTYLE_CupFinisher);
			$rightQuad->setSize(7, 3.5);

			$rightLabel = new Label_Text();
			$playerFrame->add($rightLabel);
			$rightLabel->setX($x + 13.9);
			$rightLabel->setTextSize(0.8);
			$rightLabel->setZ(10);
			$rightLabel->setText($this->maniaControl->authenticationManager->getAuthLevelAbbreviation($admin->authLevel));
			$script->addTooltip($rightLabel, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel) . " " . $admin->nickname));


			$y -= 4;
			$i++;
			if ($i % self::MAX_PLAYERS_PER_PAGE == 0) {
				unset($pageFrame);
			}
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'AdminList');
	}

} 