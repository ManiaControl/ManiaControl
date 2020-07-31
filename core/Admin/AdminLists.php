<?php

namespace ManiaControl\Admin;

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\LabelLine;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;

/**
 * Widget Class listing Authorized Players
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AdminLists implements ManialinkPageAnswerListener, CallbackListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const ACTION_OPEN_ADMIN_LIST = 'AdminList.OpenAdminList';
	const ACTION_REVOKE_RIGHTS   = 'AdminList.RevokeRights';
	const MAX_PLAYERS_PER_PAGE   = 15;

	/*
	 * Private Properties
	 */
	private $adminListShown = array();

	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	/**
	 * Construct a new PlayerList instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_OPENED, $this, 'handleWidgetOpened');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(AuthenticationManager::CB_AUTH_LEVEL_CHANGED, $this, 'updateWidget');

		// Menu Entry AdminList
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_OPEN_ADMIN_LIST, $this, 'openAdminList');
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Author);
		$itemQuad->setAction(self::ACTION_OPEN_ADMIN_LIST);
		$this->maniaControl->getActionsMenu()->addMenuItem($itemQuad, false, 50, 'Open AdminList');
	}

	/**
	 * Open Admin List Action
	 *
	 * @internal
	 * @param array  $callback
	 * @param Player $player
	 */
	public function openAdminList(array $callback, Player $player) {
		$this->showAdminLists($player);
	}

	/**
	 * Show the Admin List
	 *
	 * @api
	 * @param Player $player
	 */
	public function showAdminLists(Player $player) {
		$this->adminListShown[$player->login] = true;

		$width  = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();

		// get Admins
		$admins = $this->maniaControl->getAuthenticationManager()->getAdmins();

		//Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$paging    = new Paging();
		$script    = new Script();
		$script->addFeature($paging);
		$maniaLink->setScript($script);

		// Main frame
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame($script, $paging);
		$maniaLink->addChild($frame);

		// Start offsets
		$posX = -$width / 2;
		$posY = $height / 2;

		//Predefine description Label
		$descriptionLabel = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultDescriptionLabel();
		$frame->addChild($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->addChild($headFrame);
		$headFrame->setY($posY - 5);

		$labelLine = new LabelLine($headFrame);
		$labelLine->addLabelEntryText('Id', $posX + 5);
		$labelLine->addLabelEntryText('Nickname', $posX + 18);
		$labelLine->addLabelEntryText('Login', $posX + 70);
		$labelLine->addLabelEntryText('Actions', $posX + 120);
		$labelLine->render();

		$index     = 1;
		$posY      -= 10;
		$pageFrame = null;

		foreach ($admins as $admin) {
			if ($index % self::MAX_PLAYERS_PER_PAGE === 1) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);

				$paging->addPageControl($pageFrame);
				$posY = $height / 2 - 10;
			}

			$playerFrame = new Frame();
			$pageFrame->addChild($playerFrame);
			$playerFrame->setY($posY);

			if ($index % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->addChild($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(-0.1);
			}

			$labelLine = new LabelLine($playerFrame);
			$labelLine->addLabelEntryText($index, $posX + 5, 13);
			$labelLine->addLabelEntryText($admin->nickname, $posX + 18, 52);
			$labelLine->addLabelEntryText($admin->login, $posX + 70, 48);
			$labelLine->render();

			// Level Quad
			$rightQuad = new Quad_BgRaceScore2();
			$playerFrame->addChild($rightQuad);
			$rightQuad->setX($posX + 13);
			$rightQuad->setZ(5);
			$rightQuad->setSubStyle($rightQuad::SUBSTYLE_CupFinisher);
			$rightQuad->setSize(7, 3.5);

			$rightLabel = new Label_Text();
			$playerFrame->addChild($rightLabel);
			$rightLabel->setX($posX + 13.9);
			$rightLabel->setTextSize(0.8);
			$rightLabel->setZ(10);
			$rightLabel->setText($this->maniaControl->getAuthenticationManager()->getAuthLevelAbbreviation($admin));
			$description = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($admin) . " " . $admin->nickname;
			$rightLabel->addTooltipLabelFeature($descriptionLabel, $description);

			//Revoke Button
			if ($admin->authLevel > 0
			    && $this->maniaControl->getAuthenticationManager()->checkRight($player, $admin->authLevel + 1)
			) {
				//Settings
				$style      = Label_Text::STYLE_TextCardSmall;
				$textColor  = 'FFF';
				$quadWidth  = 24;
				$quadHeight = 3.4;

				// Quad
				$quad = new Quad_BgsPlayerCard();
				$playerFrame->addChild($quad);
				$quad->setZ(11);
				$quad->setX($posX + 130);
				$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
				$quad->setSize($quadWidth, $quadHeight);
				$quad->setAction(self::ACTION_REVOKE_RIGHTS . "." . $admin->login);

				//Label
				$label = new Label_Button();
				$playerFrame->addChild($label);
				$label->setX($posX + 130);
				$quad->setZ(12);
				$label->setStyle($style);
				$label->setTextSize(1);
				$label->setTextColor($textColor);
				$label->setText("Revoke Rights");
			}

			$posY -= 4;
			$index++;
		}

		// Render and display xml
		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $player, 'AdminList');
	}

	/**
	 * Called on ManialinkPageAnswer
	 *
	 * @internal
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId, 3);
		if (count($actionArray) <= 2) {
			return;
		}

		$action      = $actionArray[0] . '.' . $actionArray[1];
		$adminLogin  = $callback[1][1];
		$targetLogin = $actionArray[2];

		switch ($action) {
			case self::ACTION_REVOKE_RIGHTS:
				$this->maniaControl->getPlayerManager()->getPlayerActions()->revokeAuthLevel($adminLogin, $targetLogin);
				break;
		}
	}

	/**
	 * Reopen the widget on Map Begin, MapListChanged, etc.
	 *
	 * @internal
	 * @param Player $player
	 */
	public function updateWidget(Player $player) {
		foreach ($this->adminListShown as $login => $shown) {
			if ($shown) {
				$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
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
	 * @internal
	 * @param Player $player
	 */
	public function closeWidget(Player $player) {
		unset($this->adminListShown[$player->login]);
	}

	/**
	 * Unset the player if he opened another Main Widget
	 *
	 * @internal
	 * @param Player $player
	 * @param string $openedWidget
	 */
	public function handleWidgetOpened(Player $player, $openedWidget) {
		//unset when another main widget got opened
		if ($openedWidget !== 'AdminList') {
			unset($this->adminListShown[$player->login]);
		}
	}
} 