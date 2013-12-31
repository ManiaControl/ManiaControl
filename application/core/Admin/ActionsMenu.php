<?php

namespace ManiaControl\Admin;

use FML\Controls\Quads\Quad_Icons128x128_1;
use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use FML\ManiaLink;
use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Quad;
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
	const MLID_MENU = 'ActionsMenu.MLID';
	const SETTING_MENU_POSX = 'Menu Position: X';
	const SETTING_MENU_POSY = 'Menu Position: Y';
	const SETTING_MENU_ITEMSIZE = 'Menu Item Size';
	const ACTION_OPEN_ADMIN_MENU = 'ActionsMenu.OpenAdminMenu';
	const ACTION_OPEN_PLAYER_MENU = 'ActionsMenu.OpenPlayerMenu';
	
	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $manialink = null;
	private $adminMenuItems = array();
	private $playerMenuItems = array();

	/**
	 * Create a new Actions Menu
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_POSX, 156.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_POSY, -37.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_ITEMSIZE, 6.);
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerJoined');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_ADMIN_MENU, $this, 
				'openAdminMenu');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_PLAYER_MENU, $this, 
				'openPlayerMenu');
	}

	/**
	 * Add a new Menu Item
	 *
	 * @param Control $control
	 * @param bool $playerAction
	 * @param int $order
	 */
	public function addMenuItem(Control $control, $playerAction = true, $order = 0) {
		if ($playerAction) {
			$this->addPlayerMenuItem($control, $order);
		}
		else {
			$this->addAdminMenuItem($control, $order);
		}
	}

	/**
	 * Add a new Player Menu Item
	 *
	 * @param Control $control
	 * @param int $order
	 */
	public function addPlayerMenuItem(Control $control, $order = 0) {
		if (!isset($this->playerMenuItems[$order])) {
			$this->playerMenuItems[$order] = array();
		}
		array_push($this->playerMenuItems[$order], $control);
	}

	/**
	 * Add a new Admin Menu Item
	 *
	 * @param Control $control
	 * @param int $order
	 */
	public function addAdminMenuItem(Control $control, $order = 0) {
		if (!isset($this->adminMenuItems[$order])) {
			$this->adminMenuItems[$order] = array();
		}
		array_push($this->adminMenuItems[$order], $control);
	}

	/**
	 * Handle ManiaControl OnInit callback
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		$this->buildIcons();
		$manialinkText = $this->manialink->render()->saveXML();
		$players = $this->maniaControl->playerManager->getPlayers();
		foreach ($players as $player) {
			if (!$this->checkPlayerRight($player)) continue;
			$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
		}
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerJoined(array $callback) {
		$player = $callback[1];
		if (!$player || !$this->checkPlayerRight($player)) return;
		$this->buildIcons();
		$manialinkText = $this->manialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
	}

	/**
	 * Handle OpenAdminMenu Action
	 *
	 * @param array $callback
	 */
	public function openAdminMenu(array $callback, Player $player) {
		$this->buildManialink(true);
		$manialinkText = $this->manialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
	}

	/**
	 * Handle OpenPlayerMenu Action
	 *
	 * @param array $callback
	 */
	public function openPlayerMenu(array $callback, Player $player) {
		$this->buildManialink(true);
		$manialinkText = $this->manialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
	}

	/**
	 * Check if the player has access to the admin menu
	 *
	 * @param Player $player
	 * @return bool
	 */
	private function checkPlayerRight(Player $player) {
		return AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR);
	}

	/**
	 * Build the icons
	 *
	 * @param bool $forceBuild
	 */
	private function buildIcons($forceBuild = false) {
		if (is_object($this->manialink) && !$forceBuild) return;
		
		$posX = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_POSX);
		$posY = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_POSY);
		$itemSize = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_ITEMSIZE);
		$quadStyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;
		
		$manialink = new ManiaLink(self::MLID_MENU);
		
		// Player Menu Icon Frame
		$frame = new Frame();
		$manialink->add($frame);
		$frame->setPosition($posX, $posY);
		
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		
		$iconFrame = new Frame();
		$frame->add($iconFrame);
		
		$iconFrame->setSize($itemSize, $itemSize);
		$itemQuad = new Quad_Icons128x128_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Options);
		$itemQuad->setSize($itemSize, $itemSize);
		$iconFrame->add($itemQuad);
		$itemQuad->setAction(self::ACTION_OPEN_ADMIN_MENU);
		
		// Admin Menu Icon Frame
		$frame = new Frame();
		$manialink->add($frame);
		$frame->setPosition($posX, $posY - $itemSize * $itemMarginFactorY);
		
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		
		$iconFrame = new Frame();
		$frame->add($iconFrame);
		
		$iconFrame->setSize($itemSize, $itemSize);
		$itemQuad = new Quad_Icons128x128_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Custom);
		$itemQuad->setSize($itemSize, $itemSize);
		$iconFrame->add($itemQuad);
		$itemQuad->setAction(self::ACTION_OPEN_PLAYER_MENU);
		
		$this->manialink = $manialink;
	}

	/**
	 * Build the menu manialink if necessary
	 *
	 * @param bool $forceBuild
	 */
	private function buildManialink($forceBuild = false) {
		if (is_object($this->manialink) && !$forceBuild) return;
		
		$posX = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_POSX);
		$posY = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_POSY);
		$itemSize = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_ITEMSIZE);
		$quadStyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		
		$itemCount = count($this->menuItems);
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;
		
		$manialink = new ManiaLink(self::MLID_MENU);
		
		$frame = new Frame();
		$manialink->add($frame);
		$frame->setPosition($posX, $posY);
		
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($itemCount * $itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		
		$itemsFrame = new Frame();
		$frame->add($itemsFrame);
		
		// Add items
		$x = 0.5 * $itemSize * $itemMarginFactorX;
		foreach ($this->menuItems as $menuItems) {
			foreach ($menuItems as $menuItem) {
				$menuItem->setSize($itemSize, $itemSize);
				$itemsFrame->add($menuItem);
				
				$x += $itemSize * $itemMarginFactorX;
			}
		}
		
		$this->manialink = $manialink;
	}
}
