<?php

namespace ManiaControl\Admin;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Color Manager Class to give roles different colors
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ColorManager implements UsageInformationAble {
    use UsageInformationTrait;
    
    /**
     * Constants
     */
	const SETTING_COLOR_PLAYER       = 'Color of Player';
	const SETTING_COLOR_MODERATOR    = 'Color of Moderator';
	const SETTING_COLOR_ADMIN        = 'Color of Admin';
	const SETTING_COLOR_SUPERADMIN   = 'Color of SuperAdmin';
    const SETTING_COLOR_MASTERADMIN  = 'Color of MasterAdmin';
    
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
    private $maniaControl = null;
    
    public function __construct(ManiaControl $maniaControl) {
        $this->maniaControl = $maniaControl;

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_COLOR_PLAYER, '$ff0');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_COLOR_MODERATOR, '$0f9');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_COLOR_ADMIN, '$39f');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_COLOR_SUPERADMIN, '$f93');
        $this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_COLOR_MASTERADMIN, '$f00');
    }

	/**
	 * Returns the admins color by the authentication level
	 * 
	 * @param int $authLevel
	 * @return string
	 */
	public function getColorByLevel($authLevel) {
		switch ($authLevel) {
			case AuthenticationManager::AUTH_LEVEL_PLAYER:
				return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLOR_PLAYER);
			case AuthenticationManager::AUTH_LEVEL_MODERATOR:
				return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLOR_MODERATOR);
			case AuthenticationManager::AUTH_LEVEL_ADMIN:
				return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLOR_ADMIN);
			case AuthenticationManager::AUTH_LEVEL_SUPERADMIN:
				return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLOR_SUPERADMIN);
			case AuthenticationManager::AUTH_LEVEL_MASTERADMIN:
				return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLOR_MASTERADMIN);
		}
		return '';
	}

	/**
	 * Returns the admins color by the authentication name
	 * 
	 * @param string $authName
	 * @return string
	 */
	public function getColorByName($authName) {
		switch ($authName) {
			case AuthenticationManager::AUTH_NAME_PLAYER:
				return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLOR_PLAYER);
			case AuthenticationManager::AUTH_NAME_MODERATOR:
				return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLOR_MODERATOR);
			case AuthenticationManager::AUTH_NAME_ADMIN:
				return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLOR_ADMIN);
			case AuthenticationManager::AUTH_NAME_SUPERADMIN:
				return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLOR_SUPERADMIN);
			case AuthenticationManager::AUTH_NAME_MASTERADMIN:
				return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLOR_MASTERADMIN);
		}
		return '';
	}

	/**
	 * Returns the admins color by the players authentication level
	 * 
	 * @param Player $player
	 * @return string
	 */
	public function getColorByPlayer(Player $player) {
		return $this->getColorByLevel($player->authLevel);
	}
}