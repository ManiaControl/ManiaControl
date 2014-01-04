<?php

namespace ManiaControl\Admin;

use FML\Controls\Control;
use FML\Controls\Frame;
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
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_ADMIN_MENU, $this, 'openAdminMenu');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_PLAYER_MENU, $this, 'openPlayerMenu');
	}

	/**
	 * Add a new Menu Item
	 *
	 * @param Control $control
	 * @param bool    $playerAction
	 * @param int     $order
	 */
	public function addMenuItem(Control $control, $playerAction = true, $order = 0) {
		if($playerAction) {
			$this->addPlayerMenuItem($control, $order);
		} else {
			$this->addAdminMenuItem($control, $order);
		}
	}

	/**
	 * Add a new Player Menu Item
	 *
	 * @param Control $control
	 * @param int     $order
	 */
	public function addPlayerMenuItem(Control $control, $order = 0) {
		if(!isset($this->playerMenuItems[$order])) {
			$this->playerMenuItems[$order] = array();
		}
		array_push($this->playerMenuItems[$order], $control);
	}

	/**
	 * Add a new Admin Menu Item
	 *
	 * @param Control $control
	 * @param int     $order
	 */
	public function addAdminMenuItem(Control $control, $order = 0) {
		if(!isset($this->adminMenuItems[$order])) {
			$this->adminMenuItems[$order] = array();
		}
		array_push($this->adminMenuItems[$order], $control);
	}

	/**
	 * Handle ManiaControl OnInit callback
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) { //TODO render only once, but howtodo admin check then?
		//$manialinkText = $this->buildMenuIconsManialink()->render()->saveXML();
		$players = $this->maniaControl->playerManager->getPlayers();
		foreach($players as $player) {
			$manialinkText = $this->buildMenuIconsManialink($player)->render()->saveXML();
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
	 * Handle OpenAdminMenu Action
	 *
	 * @param array $callback
	 */
	public function openAdminMenu(array $callback, Player $player) {
	}

	/**
	 * Handle OpenPlayerMenu Action
	 *
	 * @param array $callback
	 */
	public function openPlayerMenu(array $callback, Player $player) {
	}

	/**
	 * Builds the Manialink
	 *
	 * @param Player $player
	 * @return ManiaLink
	 */
	private function buildMenuIconsManialink(Player $player) { //TODO Description Labels, close tooltip on click
		$posX              = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_POSX);
		$posY              = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_POSY);
		$itemSize          = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_ITEMSIZE);
		$quadStyle         = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle      = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;

		$manialink = new ManiaLink(self::MLID_MENU);

		// Admin Menu Icon Frame
		$frame = new Frame();
		$manialink->add($frame);
		$frame->setPosition($posX, $posY);

		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);


		$iconFrame = new Frame();
		$frame->add($iconFrame);

		// Create script and features
		$script = new Script();
		$manialink->setScript($script);

		$iconFrame->setSize($itemSize, $itemSize);
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_IconServers);
		$itemQuad->setSize($itemSize, $itemSize);
		$iconFrame->add($itemQuad);

		if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			//Admin Menu
			$popoutFrame = new Frame();
			$manialink->add($popoutFrame);
			$popoutFrame->setPosition($posX - $itemSize * 0.5, $posY);
			$popoutFrame->setHAlign(Control::RIGHT);
			$popoutFrame->setSize(4 * $itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);

			$quad = new Quad();
			$popoutFrame->add($quad);
			$quad->setHAlign(Control::RIGHT);
			$quad->setStyles($quadStyle, $quadSubstyle);
			$quad->setSize(count($this->adminMenuItems) * $itemSize * 1.15 + 2, $itemSize * $itemMarginFactorY);

			$popoutFrame->add($quad);

			$script->addTooltip($itemQuad, $popoutFrame, Script::OPTION_TOOLTIP_STAYONCLICK);

			// Add items
			$x = -1;
			foreach(array_reverse($this->adminMenuItems) as $menuItems) {
				foreach($menuItems as $menuItem) {
					/** @var Quad $menuItem */
					$menuItem->setSize($itemSize, $itemSize);
					$popoutFrame->add($menuItem);
					$menuItem->setX($x);
					$menuItem->setHAlign(Control::RIGHT);
					$x -= $itemSize * 1.05;
				}
			}
		}
		// Player Menu Icon Frame
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
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_IconPlayers);
		$itemQuad->setSize($itemSize, $itemSize);
		$iconFrame->add($itemQuad);


		//Player Menu
		$popoutFrame = new Frame();
		$manialink->add($popoutFrame);
		$popoutFrame->setPosition($posX - $itemSize * 0.5, $posY - $itemSize * $itemMarginFactorY);
		$popoutFrame->setHAlign(Control::RIGHT);
		$popoutFrame->setSize(4 * $itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);

		$quad = new Quad();
		$popoutFrame->add($quad);
		$quad->setHAlign(Control::RIGHT);
		$quad->setStyles($quadStyle, $quadSubstyle);
		$quad->setSize(count($this->playerMenuItems) * $itemSize * 1.15 + 2, $itemSize * $itemMarginFactorY);


		$popoutFrame->add($quad);

		$script->addTooltip($itemQuad, $popoutFrame, Script::OPTION_TOOLTIP_STAYONCLICK);

		// Add items
		$x = -1;
		foreach(array_reverse($this->playerMenuItems) as $menuItems) {
			foreach($menuItems as $menuItem) {
				/** @var Quad $menuItem */
				$menuItem->setSize($itemSize, $itemSize);
				$popoutFrame->add($menuItem);
				$menuItem->setX($x);
				$menuItem->setHAlign(Control::RIGHT);
				$x -= $itemSize * 1.05;
			}
		}

		return $manialink;
	}
}
