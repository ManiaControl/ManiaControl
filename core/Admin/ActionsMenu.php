<?php

namespace ManiaControl\Admin;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Manialinks\SidebarMenuEntryRenderable;
use ManiaControl\Manialinks\SidebarMenuManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Settings\Setting;
use ManiaControl\Settings\SettingManager;

/**
 * Class managing Actions Menus
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ActionsMenu implements SidebarMenuEntryRenderable, CallbackListener, ManialinkPageAnswerListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const MLID_MENU                    = 'ActionsMenu.MLID';
	const SETTING_MENU_POSX            = 'Menu Position: X';
	const SETTING_MENU_POSY_SHOOTMANIA = 'Shootmania Menu Position: Y';
	const SETTING_MENU_POSY_TRACKMANIA = 'Trackmania Menu Position: Y';
	const SETTING_MENU_ITEMSIZE        = 'Menu Item Size';
	const ACTION_OPEN_ADMIN_MENU       = 'ActionsMenu.OpenAdminMenu';
	const ACTION_OPEN_PLAYER_MENU      = 'ActionsMenu.OpenPlayerMenu';
	const ADMIN_MENU_ID                = 'ActionsMenu.AdminMenu';
	const PLAYER_MENU_ID               = 'ActionsMenu.PlayerMenu';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl    = null;
	private $adminMenuItems  = array();
	private $playerMenuItems = array();
	private $initCompleted   = false;

	/**
	 * Construct a new Actions Menu instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MENU_POSX, 156.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MENU_POSY_SHOOTMANIA, -37.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MENU_POSY_TRACKMANIA, 17.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MENU_ITEMSIZE, 6.);

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::AFTERINIT, $this, 'handleAfterInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerJoined');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(AuthenticationManager::CB_AUTH_LEVEL_CHANGED, $this, 'handlePlayerJoined');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'handleSettingChanged');

		$this->maniaControl->getManialinkManager()->getSidebarMenuManager()->addMenuEntry($this,SidebarMenuManager::ORDER_ADMIN_MENU, self::ADMIN_MENU_ID);
		$this->maniaControl->getManialinkManager()->getSidebarMenuManager()->addMenuEntry($this,SidebarMenuManager::ORDER_PLAYER_MENU, self::PLAYER_MENU_ID);
	}

	/**
	 * Add a new Menu Item
	 *
	 * @api
	 * @param Control $control
	 * @param bool    $playerAction
	 * @param int     $order
	 * @param string  $description
	 */
	public function addMenuItem(Control $control, $playerAction = true, $order = 0, $description = null) {
		if ($playerAction) {
			$this->addPlayerMenuItem($control, $order, $description);
		} else {
			$this->addAdminMenuItem($control, $order, $description);
		}
	}

	/**
	 * Add a new Player Menu Item
	 *
	 * @api
	 * @param Control $control
	 * @param int     $order
	 * @param string  $description
	 */
	public function addPlayerMenuItem(Control $control, $order = 0, $description = null) {
		if (!isset($this->playerMenuItems[$order])) {
			$this->playerMenuItems[$order] = array();
		}
		array_push($this->playerMenuItems[$order], array($control, $description));
		krsort($this->playerMenuItems);
		$this->rebuildAndShowMenu();
	}

	/**
	 * Build and show the menus to everyone (if a menu get made after the init)
	 *
	 * @api
	 */
	public function rebuildAndShowMenu() {
		if (!$this->initCompleted) {
			return;
		}

		//Send Menu to Admins
		$admins = $this->maniaControl->getAuthenticationManager()->getConnectedAdmins(AuthenticationManager::AUTH_LEVEL_MODERATOR);
		if (!empty($admins)) {
			$manialink = $this->buildMenuIconsManialink(true);
			$this->maniaControl->getManialinkManager()->sendManialink($manialink, $admins);
		}

		//Send Menu to Players - Players with No Admin Permisssions
		$players = $this->maniaControl->getAuthenticationManager()->getConnectedPlayers();
		if (!empty($players)) {
			$manialink = $this->buildMenuIconsManialink(false);
			$this->maniaControl->getManialinkManager()->sendManialink($manialink, $players);
		}
	}

	/**
	 * Builds the Manialink
	 *
	 * @param Player $player
	 * @return ManiaLink
	 */
	private function buildMenuIconsManialink($admin = false) {
		$adminPos          = $this->maniaControl->getManialinkManager()->getSidebarMenuManager()->getEntryPosition(self::ADMIN_MENU_ID);
		$playerPos         = $this->maniaControl->getManialinkManager()->getSidebarMenuManager()->getEntryPosition(self::PLAYER_MENU_ID);
		$itemSize          = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MENU_ITEMSIZE);
		$quadStyle         = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle      = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;

		$maniaLink = new ManiaLink(self::MLID_MENU);
		$frame     = new Frame();
		$maniaLink->addChild($frame);
		$frame->setZ(ManialinkManager::MAIN_MANIALINK_Z_VALUE);

		/*
		 * Admin Menu
		 */
		if ($admin) {
			// Admin Menu Icon Frame
			$iconFrame = new Frame();
			$frame->addChild($iconFrame);
			$iconFrame->setPosition($adminPos['x'], $adminPos['y']);

			$backgroundQuad = new Quad();
			$iconFrame->addChild($backgroundQuad);
			$backgroundQuad->setSize($itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
			$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

			$itemQuad = new Quad_Icons64x64_1();
			$iconFrame->addChild($itemQuad);
			$itemQuad->setSubStyle($itemQuad::SUBSTYLE_IconServers);
			$itemQuad->setSize($itemSize, $itemSize);

			// Admin Menu Description
			$descriptionLabel = new Label();
			$frame->addChild($descriptionLabel);
			$descriptionLabel->setPosition($adminPos['x'] - count($this->adminMenuItems) * $itemSize * 1.05 - 5, $adminPos['y']);
			$descriptionLabel->setAlign($descriptionLabel::RIGHT, $descriptionLabel::TOP);
			$descriptionLabel->setSize(40, 4);
			$descriptionLabel->setTextSize(1.4);
			$descriptionLabel->setTextColor('fff');

			// Admin Menu
			$popoutFrame = new Frame();
			$frame->addChild($popoutFrame);
			$popoutFrame->setPosition($adminPos['x'] - $itemSize * 0.5, $adminPos['y']);
			$popoutFrame->setHorizontalAlign($popoutFrame::RIGHT);
			$popoutFrame->setVisible(false);

			$backgroundQuad = new Quad();
			$popoutFrame->addChild($backgroundQuad);
			$backgroundQuad->setHorizontalAlign($backgroundQuad::RIGHT);
			$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
			$backgroundQuad->setSize(count($this->adminMenuItems) * $itemSize * 1.05 + 2, $itemSize * $itemMarginFactorY);

			$itemQuad->addToggleFeature($popoutFrame);

			// Add items
			$itemPosX = -1;
			foreach ($this->adminMenuItems as $menuItems) {
				foreach ($menuItems as $menuItem) {
					$menuQuad = $menuItem[0];
					/** @var Quad $menuQuad */
					$popoutFrame->addChild($menuQuad);
					$menuQuad->setSize($itemSize, $itemSize);
					$menuQuad->setX($itemPosX);
					$menuQuad->setHorizontalAlign($menuQuad::RIGHT);
					$itemPosX -= $itemSize * 1.05;

					if ($menuItem[1]) {
						$menuQuad->removeAllScriptFeatures();
						$description = '$s' . $menuItem[1];
						$menuQuad->addTooltipLabelFeature($descriptionLabel, $description);
					}
				}
			}
		}

		/*
		 * Player Menu
		 */
		// Player Menu Icon Frame
		$iconFrame = new Frame();
		$frame->addChild($iconFrame);
		$iconFrame->setPosition($playerPos['x'], $playerPos['y']);

		$backgroundQuad = new Quad();
		$iconFrame->addChild($backgroundQuad);
		$backgroundQuad->setSize($itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$itemQuad = new Quad_Icons64x64_1();
		$iconFrame->addChild($itemQuad);
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_IconPlayers);
		$itemQuad->setSize($itemSize, $itemSize);

		// Player Menu Description
		$descriptionLabel = new Label();
		$frame->addChild($descriptionLabel);
		$descriptionLabel->setPosition($playerPos['x'] - count($this->playerMenuItems) * $itemSize * 1.05 - 5, $playerPos['y']);
		$descriptionLabel->setAlign($descriptionLabel::RIGHT, $descriptionLabel::TOP);
		$descriptionLabel->setSize(40, 4);
		$descriptionLabel->setTextSize(1.4);
		$descriptionLabel->setTextColor('fff');

		// Player Menu
		$popoutFrame = new Frame();
		$frame->addChild($popoutFrame);
		$popoutFrame->setPosition($playerPos['x'] - $itemSize * 0.5, $playerPos['y']);
		$popoutFrame->setHorizontalAlign($popoutFrame::RIGHT);
		$popoutFrame->setVisible(false);

		$backgroundQuad = new Quad();
		$popoutFrame->addChild($backgroundQuad);
		$backgroundQuad->setHorizontalAlign($backgroundQuad::RIGHT);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setSize(count($this->playerMenuItems) * $itemSize * 1.05 + 2, $itemSize * $itemMarginFactorY);

		$itemQuad->addToggleFeature($popoutFrame);

		// Add items
		$itemPosX = -1;
		foreach ($this->playerMenuItems as $menuItems) {
			foreach ($menuItems as $menuItem) {
				$menuQuad = $menuItem[0];
				/** @var Quad $menuQuad */
				$popoutFrame->addChild($menuQuad);
				$menuQuad->setSize($itemSize, $itemSize);
				$menuQuad->setX($itemPosX);
				$menuQuad->setHorizontalAlign($menuQuad::RIGHT);
				$itemPosX -= $itemSize * 1.05;

				if ($menuItem[1]) {
					$menuQuad->removeAllScriptFeatures();
					$description = '$s' . $menuItem[1];
					$menuQuad->addTooltipLabelFeature($descriptionLabel, $description);
				}
			}
		}

		return $maniaLink;
	}

	/**
	 * Add a new Admin Menu Item
	 *
	 * @api
	 * @param Control $control
	 * @param int     $order
	 * @param string  $description
	 */
	public function addAdminMenuItem(Control $control, $order = 0, $description = null) {
		if (!isset($this->adminMenuItems[$order])) {
			$this->adminMenuItems[$order] = array();
		}
		array_push($this->adminMenuItems[$order], array($control, $description));
		krsort($this->adminMenuItems);
		$this->rebuildAndShowMenu();
	}

	/**
	 * Removes a Menu Item
	 *
	 * @api
	 * @param int  $order
	 * @param bool $playerAction
	 */
	public function removeMenuItem($order, $playerAction = true) {
		if ($playerAction) {
			if (isset($this->playerMenuItems[$order])) {
				unset($this->playerMenuItems[$order]);
			}
		} else {
			if (isset($this->adminMenuItems[$order])) {
				unset($this->adminMenuItems[$order]);
			}
		}
		$this->rebuildAndShowMenu();
	}

	/**
	 * Handle ManiaControl AfterInit callback
	 *
	 * @internal
	 */
	public function handleAfterInit() {
		$this->initCompleted = true;
		$this->rebuildAndShowMenu();
	}

	/**
	 * Handle PlayerJoined callback
	 *
	 * @internal
	 * @param Player $player
	 */
	public function handlePlayerJoined(Player $player) {
		$maniaLink = $this->buildMenuIconsManialink($player);
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $player);
	}

	/**
	 * Handle Setting Changed Callback
	 *
	 * @param Setting $setting
	 */
	public function handleSettingChanged(Setting $setting) {
		if (!$setting->belongsToClass($this)) {
			return;
		}

		$this->rebuildAndShowMenu();
	}

	/**
	 *  Call here the function which updates the MenuIcon Manialink
	 */
	public function renderMenuIcon() {
		$this->rebuildAndShowMenu();
	}
}
