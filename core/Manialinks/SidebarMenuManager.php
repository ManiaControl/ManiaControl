<?php

namespace ManiaControl\Manialinks;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Structures\ShootMania\Models\Position;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

/**
 * Class managing the Sidebar icons
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class SidebarMenuManager implements UsageInformationAble, CallbackListener {
	use UsageInformationTrait;

	/* Settings */
	const SETTING_SIDEBAR_POSX            = 'Sidebar X Position';
	const SETTING_SIDEBAR_POSY_SHOOTMANIA = 'Sidebar Y Position (Shootmania)';
	const SETTING_SIDEBAR_POSY_TRACKMANIA = 'Sidebar Y Position (Trackmania)';
	const SETTING_MENU_ITEMSIZE           = 'Size of menu items';

	const ORDER_ADMIN_MENU  = 10;
	const ORDER_PLAYER_MENU = 20;

	/** @var ManiaControl $maniaControl */
	private $maniaControl;
	/** @var \ManiaControl\Manialinks\SidebarMenuEntry[] $menuEntries */
	private $menuEntries = array();

	/**
	 * SidebarMenuManager constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SIDEBAR_POSX, 156);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SIDEBAR_POSY_SHOOTMANIA, -17);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SIDEBAR_POSY_TRACKMANIA, 17);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MENU_ITEMSIZE, 6);
	}

	/**
	 * Returns Position of the Sidebar (PositionObject with x and y)
	 *
	 * @return Position
	 * @api
	 */
	public function getSidebarPosition() {
		$posX = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SIDEBAR_POSX);

		if ($this->maniaControl->getMapManager()->getCurrentMap()->getGame() === 'sm') {
			$posY = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SIDEBAR_POSY_SHOOTMANIA);
		} else {
			$posY = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SIDEBAR_POSY_TRACKMANIA);
		}
		$pos = new Position();
		$pos->setX($posX);
		$pos->setY($posY);

		return $pos;
	}

	/**
	 * Returns the number of elements above the admin menu
	 * Used to make the y-value setting of the sidebar relative to the admin menu
	 *
	 * @return int
	 */
	private function getElementCountBeforeAdminMenu() {
		$count = 0;
		foreach ($this->menuEntries as $k => $entry) {
			if ($k < SidebarMenuManager::ORDER_ADMIN_MENU) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Returns PositionObject of a menu item of the sidebar, or null if it's not found
	 *
	 * @api
	 * @param string $id
	 * @return Position|null
	 */
	public function getEntryPosition($id) {
		$itemSize         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MENU_ITEMSIZE);
		$itemMarginFactor = 1.2;
		$pos              = $this->getSidebarPosition();

		$count = $this->getElementCountBeforeAdminMenu();
		$pos->setY($pos->getY() + $itemSize * $itemMarginFactor * $count);

		foreach ($this->menuEntries as $entry) {
			if ($entry->getId() == $id) {
				return $pos;
			}
			$pos->setY($pos->getY() - $itemSize * $itemMarginFactor);
		}

		$this->maniaControl->getErrorHandler()->triggerDebugNotice('SidebarMenuEntry id:' . $id . ' not found');
		return null;
	}


	/**
	 * Registers an Entry to the SidebarMenu
	 * Get the associated position with getEntryPosition($id)
	 *
	 * @api
	 * @param                                                   $order
	 * @param                                                   $id
	 * @param \ManiaControl\Manialinks\SidebarMenuEntryListener $listener
	 * @param                                                   $renderMethod
	 * @return bool
	 */
	public function addMenuEntry($order, $id, SidebarMenuEntryListener $listener, $renderMethod) {
		if ((!is_string($renderMethod) || !method_exists($listener, $renderMethod)) && !is_callable($renderMethod)) {
			trigger_error("Given Listener (" . get_class($listener) . ") can't handle Timer Callback (No Method '{$renderMethod}')!");
			return false;
		}

		if (isset($this->menuEntries[$order])) {
			if ($this->menuEntries[$order]->getId() != $id) {
				return $this->addMenuEntry($order + 1, $id, $listener, $renderMethod);
			}
		}

		$entry = new SidebarMenuEntry($listener, $renderMethod);
		$entry->setId($id);

		$this->menuEntries[$order] = $entry;
		ksort($this->menuEntries);

		$this->updateMenuEntries();

		return true;
	}

	/**
	 * Call all user functions
	 */
	private function updateMenuEntries() {
		foreach ($this->menuEntries as $listening) {

			// Call the User Function
			$listening->triggerCallback();
		}
	}


	/**
	 * @api
	 * @param SidebarMenuEntryListener                            $listener
	 * @param                                                     $id
	 */
	public function deleteMenuEntry(SidebarMenuEntryListener $listener, $id) {
		foreach ($this->menuEntries as $k => $entry) {
			if ($entry->getId() == $id) {
				unset($this->menuEntries[$k]);
			}
		}
		$this->updateMenuEntries();
	}

	/**
	 * @api
	 * @param \ManiaControl\Manialinks\SidebarMenuEntryListener $listener
	 */
	public function deleteMenuEntries(SidebarMenuEntryListener $listener) {
		foreach ($this->menuEntries as $k => $entry) {
			if ($entry->listener == $listener) {
				unset($this->menuEntries[$k]);
			}
		}

		$this->updateMenuEntries();
	}

}