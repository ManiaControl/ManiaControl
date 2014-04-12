<?php

namespace ManiaControl\Admin;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;

/**
 * Widget Class listing Authorized Players
 * 
 * @author kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AdminLists implements ManialinkPageAnswerListener, CallbackListener {
	/*
	 * Constants
	 */
	const ACTION_OPEN_ADMINLISTS = "AdminList.OpenAdminLists";
	const ACTION_REVOKE_RIGHTS   = "AdminList.RevokeRights";
	const MAX_PLAYERS_PER_PAGE   = 15;

	/*
	 * Private Properties
	 */
	private $adminListShown = array();

	/**
	 * Create a PlayerList Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_OPENED, $this, 'handleWidgetOpened');
		$this->maniaControl->callbackManager->registerCallbackListener(AuthenticationManager::CB_AUTH_LEVEL_CHANGED, $this, 'updateWidget');

		// Menu Entry AdminList
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_ADMINLISTS, $this, 'openAdminList');
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Author);
		$itemQuad->setAction(self::ACTION_OPEN_ADMINLISTS);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, false, 50, 'Open Adminlist');
	}

	/**
	 * Open Admin List Action
	 * 
	 * @param array $callback
	 * @param Player $player
	 */
	public function openAdminList(array $callback, Player $player) {
		$this->showAdminLists($player);
	}

	/**
	 * Show the Admin List
	 * 
	 * @param Player $player
	 */
	public function showAdminLists(Player $player) {
		$this->adminListShown[$player->login] = true;

		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

		// get Admins
		$admins  = $this->maniaControl->authenticationManager->getAdmins();
		$pagesId = '';
		if (count($admins) > self::MAX_PLAYERS_PER_PAGE) {
			$pagesId = 'AdminListPages';
		}

		//Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $pagesId);
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
		$array = array("Id" => $x + 5, "Nickname" => $x + 18, "Login" => $x + 70, "Actions" => $x + 120);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$i          = 1;
		$y          = $y - 10;
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

			//Revoke Button
			if ($admin->authLevel > 0 && $this->maniaControl->authenticationManager->checkRight($player, $admin->authLevel + 1)) {
				//Settings
				$style      = Label_Text::STYLE_TextCardSmall;
				$textColor  = 'FFF';
				$quadWidth  = 24;
				$quadHeight = 3.4;

				// Quad
				$quad = new Quad_BgsPlayerCard();
				$playerFrame->add($quad);
				$quad->setZ(11);
				$quad->setX($x + 130);
				$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
				$quad->setSize($quadWidth, $quadHeight);
				$quad->setAction(self::ACTION_REVOKE_RIGHTS . "." . $admin->login);

				//Label
				$label = new Label_Button();
				$playerFrame->add($label);
				$label->setX($x + 130);
				$quad->setZ(12);
				$label->setAlign(Control::CENTER, Control::CENTER);
				$label->setStyle($style);
				$label->setTextSize(1);
				$label->setTextColor($textColor);
				$label->setText("Revoke Rights");
			}

			$y -= 4;
			$i++;
			if (($i - 1) % self::MAX_PLAYERS_PER_PAGE == 0) {
				unset($pageFrame);
			}
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'AdminList');
	}

	/**
	 * Called on ManialinkPageAnswer
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId, 3);
		if (count($actionArray) <= 2) {
			return;
		}

		$action      = $actionArray[0] . "." . $actionArray[1];
		$adminLogin  = $callback[1][1];
		$targetLogin = $actionArray[2];

		switch($action) {
			case self::ACTION_REVOKE_RIGHTS:
				$this->maniaControl->playerManager->playerActions->revokeAuthLevel($adminLogin, $targetLogin);
				break;
		}
	}

	/**
	 * Reopen the widget on Map Begin, MapListChanged, etc.
	 *
	 * @param array $callback
	 */
	public function updateWidget(Player $player) {
		foreach($this->adminListShown as $login => $shown) {
			if ($shown) {
				$player = $this->maniaControl->playerManager->getPlayer($login);
				if ($player) {
					$this->showAdminLists($player);
				} else {
					unset($this->adminListShown[$login]);
				}
			}
		}
	}

	/**
	 * Closes the widget
	 *
	 * @param \ManiaControl\Players\Player $player
	 */
	public function closeWidget(Player $player) {
		unset($this->adminListShown[$player->login]);
	}

	/**
	 * Unset the player if he opened another Main Widget
	 *
	 * @param Player $player
	 * @param        $openedWidget
	 */
	public function handleWidgetOpened(Player $player, $openedWidget) {
		//unset when another main widget got opened
		if ($openedWidget != 'AdminList') {
			unset($this->adminListShown[$player->login]);
		}
	}
} 