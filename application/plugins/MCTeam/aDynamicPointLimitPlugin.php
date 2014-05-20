<?php

namespace MCTeam;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Settings\Setting;
use ManiaControl\Settings\SettingManager;

/**
 * Dynamic Point Limit Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DynamicPointLimitPlugin implements CallbackListener, CommandListener, Plugin {
	/*
	 * Constants
	 */
	const ID      = 21;
	const VERSION = 0.2;
	const NAME    = 'Dynamic Point Limit Plugin';
	const AUTHOR  = 'MCTeam';

	const SETTING_POINT_LIMIT_MULTIPLIER = 'Point Limit Multiplier';
	const SETTING_POINT_LIMIT_OFFSET     = 'Point Limit Offset';
	const SETTING_MIN_POINT_LIMIT        = 'Minimum Point Limit';
	const SETTING_MAX_POINT_LIMIT        = 'Maximum Point Limit';
	const SETTING_ACCEPT_OTHER_MODES     = 'Activate in other Modes than Royal';

	const CACHE_SPEC_STATUS = 'SpecStatus';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $lastPointLimit = null;

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
		$maniaControl->settingManager->initSetting(get_class(), self::SETTING_POINT_LIMIT_MULTIPLIER, 10);
		$maniaControl->settingManager->initSetting(get_class(), self::SETTING_POINT_LIMIT_OFFSET, 0);
		$maniaControl->settingManager->initSetting(get_class(), self::SETTING_MIN_POINT_LIMIT, 30);
		$maniaControl->settingManager->initSetting(get_class(), self::SETTING_MAX_POINT_LIMIT, 200);
		$maniaControl->settingManager->initSetting(get_class(), self::SETTING_ACCEPT_OTHER_MODES, false);
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return self::NAME;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return self::AUTHOR;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return 'Plugin offering a dynamic Point Limit according to the Number of Players on the Server.';
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$allowOthers = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_ACCEPT_OTHER_MODES);
		if (!$allowOthers && $this->maniaControl->server->titleId != 'SMStormRoyal@nadeolabs') {
			$error = 'This plugin only supports Royal (check Settings)!';
			throw new \Exception($error);
		}

		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'updatePointLimit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'updatePointLimit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERINFOCHANGED, $this, 'handlePlayerInfoChangedCallback');

		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::BEGINROUND, $this, 'updatePointLimit');
		$this->maniaControl->callbackManager->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'handleSettingChangedCallback');

		$this->updatePointLimit();
	}

	/**
	 * Update Point Limit
	 */
	public function updatePointLimit() {
		$numberOfPlayers = $this->maniaControl->playerManager->getPlayerCount();

		$multiplier = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_POINT_LIMIT_MULTIPLIER);
		$offset     = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_POINT_LIMIT_OFFSET);
		$minValue   = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MIN_POINT_LIMIT);
		$maxValue   = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MAX_POINT_LIMIT);

		$pointLimit = $offset + $numberOfPlayers * $multiplier;
		if ($pointLimit < $minValue) {
			$pointLimit = $minValue;
		}
		if ($pointLimit > $maxValue) {
			$pointLimit = $maxValue;
		}
		$pointLimit = (int)$pointLimit;

		if ($this->lastPointLimit != $pointLimit) {
			$newSettings = array('S_MapPointsLimit' => $pointLimit);
			$this->maniaControl->client->setModeScriptSettings($newSettings);

			$message = "Dynamic PointLimit changed to: {$pointLimit}!";
			if ($this->lastPointLimit !== null) {
				$message .= " (From {$this->lastPointLimit})";
			}
			$this->maniaControl->chat->sendChat($message);

			$this->lastPointLimit = $pointLimit;
		}
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
	}

	/**
	 * Handle Setting Changed Callback
	 *
	 * @param Setting $setting
	 */
	public function handleSettingChangedCallback(Setting $setting) {
		if (!$setting->belongsToClass($this)) {
			return;
		}

		$this->updatePointLimit();
	}

	/**
	 * Handle Player Info Changed Callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerInfoChangedCallback(Player $player) {
		$lastSpecStatus = $player->getCache($this, self::CACHE_SPEC_STATUS);
		$newSpecStatus  = $player->isSpectator;
		if ($newSpecStatus === $lastSpecStatus && $lastSpecStatus !== null) {
			return;
		}
		$player->setCache($this, self::CACHE_SPEC_STATUS, $newSpecStatus);
		$this->updatePointLimit();
	}
}
