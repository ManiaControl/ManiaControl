<?php

namespace ManiaControl\Manialinks;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;


class SidebarMenuManager implements UsageInformationAble, CallbackListener {
	use UsageInformationTrait;

	/* Settings */
	const SETTING_SIDEBAR_POSX            = 'Sidebar X Position';
	const SETTING_SIDEBAR_POSY_SHOOTMANIA = 'Sidebar Y Position (Shootmania)';
	const SETTING_SIDEBAR_POSY_TRACKMANIA = 'Sidebar Y Position (Trackmania)';
	const SETTING_MENU_ITEMSIZE           = 'Size of menu items';

	const ORDER_ADMIN_MENU  = 10;
	const ORDER_PLAYER_MENU = 20;

	/* @var $maniaControl ManiaControl */
	private $maniaControl;
	private $menuEntries = array();
	private $yPositions  = array();

	function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SIDEBAR_POSX, 156);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SIDEBAR_POSY_SHOOTMANIA, -37);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SIDEBAR_POSY_TRACKMANIA, 17);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MENU_ITEMSIZE, 6);
	}

	/**
	 * Returns array('x' => xPosition, 'y' => yPosition) of the Sidebar
	 *
	 * @return array
	 * @api
	 */
	public function getSidebarPosition() {
		$posX = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SIDEBAR_POSX);

		if ($this->maniaControl->getMapManager()->getCurrentMap()->getGame() === 'sm') {
			$posY = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SIDEBAR_POSY_SHOOTMANIA);
		} else {
			$posY = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SIDEBAR_POSY_TRACKMANIA);
		}

		return array('x' => $posX, 'y' => $posY);
	}

	/**
	 * Returns array('x' => xPosition, 'y' => yPosition) of a menu item of the sidebar
	 *
	 * @param string $id
	 * @return array|null
	 */
	public function getEntryPosition($id) {
		$itemSize = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MENU_ITEMSIZE);
		$pos      = $this->getSidebarPosition();
		$posX     = $pos['x'];
		$posY     = $pos['y'];

		if (isset($this->yPositions[$id])) {
			return array('x' => $posX, 'y' => $this->yPositions[$id]);
		}

		foreach ($this->menuEntries as $entry) {
			if ($entry == $id) {
				$this->yPositions[$id] = $posY;
				return array('x' => $posX, 'y' => $posY);
			}
			$posY -= $itemSize * 1.05;
		}

		$this->maniaControl->getErrorHandler()->triggerDebugNotice('SidebarMenuEntry id:' . $id . ' not found');
		return null;
	}

	/**
	 * Registers an Entry to the SidebarMenu
	 * Get the associated position with getEntryPosition($id)
	 *
	 * @param int    $order
	 * @param string $id
	 * @api
	 */
	public function addMenuEntry($order, $id) {
		if (isset($this->menuEntries[$order])) {
			if ($this->menuEntries[$order] != $id) {
				$this->addMenuEntry($order + 1, $id);
			}
		}
		$this->menuEntries[$order] = $id;
		ksort($this->menuEntries);
		$this->yPositions = array();
	}


	/**
	 * Deletes an Entry from the SidebarMenu
	 *
	 * @param string $id
	 */
	public function deleteMenuEntry($id) {
		foreach ($this->menuEntries as $k => $entry) {
			if ($entry == $id) {
				array_splice($this->menuEntries, $k, 1);
				$this->yPositions = array();
			}
		}
	}

}