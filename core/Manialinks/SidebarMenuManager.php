<?php

namespace ManiaControl\Manialinks\SidebarMenu;

use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;


class SidebarMenuManager implements UsageInformationAble {
	use UsageInformationTrait;

	const SIDEBAR_MANIALINK_ID = 'SidebarMenuManager.SidebarMenu';
	const ADMIN_MENU_ORDER     = 10;
	const PLAYER_MENU_ORDER    = 20;

	/* Settings */
	const SETTING_SIDEBAR_POSX            = 'Sidebar X Position';
	const SETTING_SIDEBAR_POSY_SHOOTMANIA = 'Sidebar Y Position (Shootmania)';
	const SETTING_SIDEBAR_POSY_TRACKMANIA = 'Sidebar Y Position (Trackmania)';
	const SETTING_MENU_ITEMSIZE           = 'Size of menu items';

	/* @var $maniaControl ManiaControl */
	private $maniaControl;
	private $menuEntries = array();

	function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SIDEBAR_POSX, 156);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SIDEBAR_POSY_SHOOTMANIA, -37);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SIDEBAR_POSY_TRACKMANIA, 17);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MENU_ITEMSIZE, 6);

	}

	public function addMenuEntry(SidebarMenuEntry $entry, $order) {
		if (isset($this->menuEntries[$order])) {
			$this->addMenuEntry($entry, $order + 1);
		}
		array_push($menuEntries, $entry);
		ksort($this->menuEntries);
		$this->updateManiaLink();
	}

	private function itemsBeforeAdmin() {
		$count = 0;
		foreach ($this->menuEntries as $key => $entry) {
			if ($key < self::ADMIN_MENU_ORDER) {
				$count++;
			}
		}
		return $count;
	}


	private function updateManiaLink() {
		$itemSize = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MENU_ITEMSIZE);
		$posX     = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SIDEBAR_POSX);
		if ($this->maniaControl->getMapManager()->getCurrentMap()->getGame() === 'sm') {
			$posY = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SIDEBAR_POSY_SHOOTMANIA);
		} else {
			$posY = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SIDEBAR_POSY_TRACKMANIA);
		}
		$quadStyle         = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle      = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;

		//Calculate X relative to AdminMenu
		$posX -= $itemSize * 1.05 * $this->itemsBeforeAdmin();

		$maniaLink = new ManiaLink(self::SIDEBAR_MANIALINK_ID);
		$frame     = new Frame();
		$maniaLink->addChild($frame);
		$frame->setZ(ManialinkManager::MAIN_MANIALINK_Z_VALUE);
		$frame->setPosition($posX, $posY);

		$posX = 0;
		/** @var SidebarMenuEntry $entry */
		foreach ($this->menuEntries as $entry) {
			$iconFrame = new Frame();
			$frame->addChild($iconFrame);
			$iconFrame->setX($posX);

			$background = new Quad();
			$frame->addChild($background);
			$background->setStyles($quadStyle, $quadSubstyle);
			$background->setSize($itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);

			$icon = $entry->getIcon();
			$frame->addChild($icon);
			if($entry->getDescription()){
				
			}
		}


		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink);
	}

}