<?php

namespace ManiaControl\Admin;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Class managing Actions Menus
 *
 * @author steeffeen & kremsy
 */
class ActionsMenu implements CallbackListener, ManialinkPageAnswerListener {
	/**
	 * Constants
	 */
	const MLID_MENU               = 'ActionsMenu.MLID';
	const SETTING_MENU_POSX       = 'Menu Position: X';
	const SETTING_MENU_POSY       = 'Menu Position: Y';
	const SETTING_MENU_ITEMSIZE   = 'Menu Item Size';
	const ACTION_OPEN_ADMIN_MENU  = 'ActionsMenu.OpenAdminMenu';
	const ACTION_OPEN_PLAYER_MENU = 'ActionsMenu.OpenPlayerMenu';

	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $adminMenuItems = array();
	private $playerMenuItems = array();
	private $initCompleted = false;

	/**
	 * Create a new Actions Menu
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_POSX, 156.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_POSY, -17.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_ITEMSIZE, 6.);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerJoined');
		$this->maniaControl->callbackManager->registerCallbackListener(AuthenticationManager::CB_AUTH_LEVEL_CHANGED, $this, 'handlePlayerJoined');
	}

	/**
	 * Add a new Menu Item
	 *
	 * @param Control $control
	 * @param bool    $playerAction
	 * @param int     $order
	 * @param string  $description
	 */
	public function addMenuItem(Control $control, $playerAction = true, $order = 0, $description = null) {
		if($playerAction) {
			$this->addPlayerMenuItem($control, $order, $description);
		} else {
			$this->addAdminMenuItem($control, $order, $description);
		}
	}

	/**
	 * Add a new Player Menu Item
	 *
	 * @param Control $control
	 * @param int     $order
	 * @param string  $description
	 */
	public function addPlayerMenuItem(Control $control, $order = 0, $description = null) {
		if(!isset($this->playerMenuItems[$order])) {
			$this->playerMenuItems[$order] = array();
		}
		array_push($this->playerMenuItems[$order], array($control, $description));
		krsort($this->playerMenuItems);
		$this->rebuildAndShowMenu();
	}

	/**
	 * Add a new Admin Menu Item
	 *
	 * @param Control $control
	 * @param int     $order
	 * @param string  $description
	 */
	public function addAdminMenuItem(Control $control, $order = 0, $description = null) {
		if(!isset($this->adminMenuItems[$order])) {
			$this->adminMenuItems[$order] = array();
		}
		array_push($this->adminMenuItems[$order], array($control, $description));
		krsort($this->adminMenuItems);
		$this->rebuildAndShowMenu();
	}

	/**
	 * Handle ManiaControl OnInit callback
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		$this->initCompleted = true;
		$this->rebuildAndShowMenu();
	}

	/**
	 * Build and show the menus to everyone (if a menu get made after the init)
	 */
	public function rebuildAndShowMenu() {
		if(!$this->initCompleted) {
			return;
		}
		$players = $this->maniaControl->playerManager->getPlayers();
		foreach($players as $player) {
			$manialink     = $this->buildMenuIconsManialink($player);
			$manialinkText = $manialink->render()->saveXML();
			$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
		}
	}

	/**
	 * Handle PlayerJoined callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerJoined(array $callback) {
		$player        = $callback[1];
		$manialinkText = $this->buildMenuIconsManialink($player)->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
	}

	/**
	 * Builds the Manialink
	 *
	 * @param Player $player
	 * @return ManiaLink
	 */
	private function buildMenuIconsManialink(Player $player) {
		$posX              = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_POSX);
		$posY              = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_POSY);
		$itemSize          = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_ITEMSIZE);
		$shootManiaOffset  = $this->maniaControl->manialinkManager->styleManager->getDefaultIconOffsetSM();
		$quadStyle         = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle      = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;

		// Get Title Id
		$titleId     = $this->maniaControl->server->titleId;
		$titlePrefix = strtoupper(substr($titleId, 0, 2));

		//If game is shootmania lower the icons position by 20
		if($titlePrefix == 'SM') {
			$posY -= $shootManiaOffset;
		}

		$manialink = new ManiaLink(self::MLID_MENU);
		$script    = new Script();
		$manialink->setScript($script);

		/*
		 * Admin Menu
		 */
		if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			// Admin Menu Icon Frame
			$iconFrame = new Frame();
			$manialink->add($iconFrame);
			$iconFrame->setPosition($posX, $posY);

			$backgroundQuad = new Quad();
			$iconFrame->add($backgroundQuad);
			$backgroundQuad->setSize($itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
			$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

			$itemQuad = new Quad_Icons64x64_1();
			$iconFrame->add($itemQuad);
			$itemQuad->setSubStyle($itemQuad::SUBSTYLE_IconServers);
			$itemQuad->setSize($itemSize, $itemSize);

			// Admin Menu Description Label
			$descriptionFrame = new Frame();
			$manialink->add($descriptionFrame);
			$descriptionFrame->setPosition($posX - count($this->adminMenuItems) * $itemSize * 1.15 - 6, $posY);

			$descriptionLabel = new Label();
			$descriptionFrame->add($descriptionLabel);
			$descriptionLabel->setAlign(Control::RIGHT, Control::TOP);
			$descriptionLabel->setSize(40, 4);
			$descriptionLabel->setTextSize(1.4);
			$descriptionLabel->setTextColor('fff');

			// Admin Menu
			$popoutFrame = new Frame();
			$manialink->add($popoutFrame);
			$popoutFrame->setPosition($posX - $itemSize * 0.5, $posY);
			$popoutFrame->setHAlign(Control::RIGHT);
			$popoutFrame->setSize(4 * $itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
			$popoutFrame->setVisible(false);

			$backgroundQuad = new Quad();
			$popoutFrame->add($backgroundQuad);
			$backgroundQuad->setHAlign(Control::RIGHT);
			$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
			$backgroundQuad->setSize(count($this->adminMenuItems) * $itemSize * 1.15 + 2, $itemSize * $itemMarginFactorY);

			$script->addToggle($itemQuad, $popoutFrame);

			// Add items
			$x = -1;
			foreach($this->adminMenuItems as $menuItems) {
				foreach($menuItems as $menuItem) {
					$menuQuad = $menuItem[0];
					/**
					 *
					 * @var Quad $menuQuad
					 */
					$popoutFrame->add($menuQuad);
					$menuQuad->setSize($itemSize, $itemSize);
					$menuQuad->setX($x);
					$menuQuad->setHAlign(Control::RIGHT);
					$x -= $itemSize * 1.05;

					if($menuItem[1]) {
						$description = '$s' . $menuItem[1];
						$script->addTooltip($menuQuad, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => $description));
					}
				}
			}
		}

		/*
		 * Player Menu
		 */
		// Player Menu Icon Frame
		$iconFrame = new Frame();
		$manialink->add($iconFrame);
		$iconFrame->setPosition($posX, $posY - $itemSize * $itemMarginFactorY);

		$backgroundQuad = new Quad();
		$iconFrame->add($backgroundQuad);
		$backgroundQuad->setSize($itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$itemQuad = new Quad_Icons64x64_1();
		$iconFrame->add($itemQuad);
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_IconPlayers);
		$itemQuad->setSize($itemSize, $itemSize);

		// Player Menu Description Frame
		$descriptionFrame = new Frame();
		$manialink->add($descriptionFrame);
		$descriptionFrame->setPosition($posX - count($this->playerMenuItems) * $itemSize * 1.15 - 6, $posY - $itemSize * $itemMarginFactorY);

		$descriptionLabel = new Label();
		$descriptionFrame->add($descriptionLabel);
		$descriptionLabel->setAlign(Control::RIGHT, Control::TOP);
		$descriptionLabel->setSize(40, 4);
		$descriptionLabel->setTextSize(1.4);
		$descriptionLabel->setTextColor('fff');

		// Player Menu
		$popoutFrame = new Frame();
		$manialink->add($popoutFrame);
		$popoutFrame->setPosition($posX - $itemSize * 0.5, $posY - $itemSize * $itemMarginFactorY);
		$popoutFrame->setHAlign(Control::RIGHT);
		$popoutFrame->setSize(4 * $itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$popoutFrame->setVisible(false);

		$backgroundQuad = new Quad();
		$popoutFrame->add($backgroundQuad);
		$backgroundQuad->setHAlign(Control::RIGHT);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setSize(count($this->playerMenuItems) * $itemSize * 1.15 + 2, $itemSize * $itemMarginFactorY);
		$script->addToggle($itemQuad, $popoutFrame);

		// Add items
		$x = -1;
		foreach($this->playerMenuItems as $menuItems) {
			foreach($menuItems as $menuItem) {
				$menuQuad = $menuItem[0];
				/**
				 *
				 * @var Quad $menuQuad
				 */
				$popoutFrame->add($menuQuad);
				$menuQuad->setSize($itemSize, $itemSize);
				$menuQuad->setX($x);
				$menuQuad->setHAlign(Control::RIGHT);
				$x -= $itemSize * 1.05;

				if($menuItem[1]) {
					$description = '$s' . $menuItem[1];
					$script->addTooltip($menuQuad, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => $description));
				}
			}
		}

		return $manialink;
	}
}
