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
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Dynamic Point Limit Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
// TODO: test setpointlimit command
class DynamicPointLimitPlugin implements CallbackListener, CommandListener, Plugin {
	/*
	 * Constants
	 */
	const ID      = 21;
	const VERSION = 0.3;
	const NAME    = 'Dynamic Point Limit Plugin';
	const AUTHOR  = 'MCTeam';

	const SETTING_POINT_LIMIT_MULTIPLIER = 'Point Limit Multiplier';
	const SETTING_POINT_LIMIT_OFFSET     = 'Point Limit Offset';
	const SETTING_MIN_POINT_LIMIT        = 'Minimum Point Limit';
	const SETTING_MAX_POINT_LIMIT        = 'Maximum Point Limit';
	const SETTING_ACCEPT_OTHER_MODES     = 'Activate in other Modes than Royal';

	const CACHE_SPEC_STATUS = 'SpecStatus';

	/*
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $lastPointLimit = null;
	private $staticMode = null;

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
		if (!$allowOthers && $this->maniaControl->server->titleId !== 'SMStormRoyal@nadeolabs') {
			$error = 'This plugin only supports Royal (check Settings)!';
			throw new \Exception($error);
		}

		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'updatePointLimit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'updatePointLimit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERINFOCHANGED, $this, 'handlePlayerInfoChangedCallback');

		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::BEGINROUND, $this, 'updatePointLimit');
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'handleSettingChangedCallback');

		$this->maniaControl->commandManager->registerCommandListener('setpointlimit', $this, 'commandSetPointlimit', true, 'Setpointlimit XXX or auto');

		$this->updatePointLimit();
	}

	/**
	 * Update Point Limit
	 */
	public function updatePointLimit() {
		if ($this->staticMode) {
			return;
		}
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

		if ($this->lastPointLimit !== $pointLimit) {
			try {
				$this->maniaControl->client->setModeScriptSettings(array('S_MapPointsLimit' => $pointLimit));
				$message = "Dynamic PointLimit changed to: {$pointLimit}!";
				if ($this->lastPointLimit !== null) {
					$message .= " (From {$this->lastPointLimit})";
				}
				$this->maniaControl->chat->sendInformation($message);
				$this->lastPointLimit = $pointLimit;
			} catch (GameModeException $exception) {
				$this->maniaControl->chat->sendExceptionToAdmins($exception);
			}
		}
	}

	/**
	 * Handle SetPointLimit Command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function commandSetPointlimit(array $chatCallback, Player $player) {
		$commandParts = explode(' ', $chatCallback[1][2]);
		if (count($commandParts) < 2) {
			$this->maniaControl->chat->sendUsageInfo('Example: //setpointlimit auto', $player);
			return;
		}
		$value = strtolower($commandParts[1]);
		if ($value === "auto") {
			$this->staticMode = false;
			$this->maniaControl->chat->sendInformation('Enabled Dynamic PointLimit!');
			$this->updatePointLimit();
		} else {
			if (is_numeric($value)) {
				$value = (int)$value;
				if ($value <= 0) {
					$this->maniaControl->chat->sendError('PointLimit needs to be greater than Zero.', $player);
					return;
				}
				try {
					$this->maniaControl->client->setModeScriptSettings(array('S_MapPointsLimit' => $value));
					$this->staticMode     = true;
					$this->lastPointLimit = $value;
					$this->maniaControl->chat->sendInformation("PointLimit changed to: {$value} (Fixed)");
				} catch (GameModeException $exception) {
					$this->maniaControl->chat->sendException($exception, $player);
				}
			} else {
				$this->maniaControl->chat->sendUsageInfo('Example: //setpointlimit 150', $player);
			}
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
	 * Handle BeginMap Callback
	 *
	 * @param Setting $setting
	 */
	public function handleBeginMap() {
		if ($this->staticMode)
		{
			
			$this->maniaControl->chat->sendChat('$fffPointlimit fixed at '.$this->lastPointLimit.'.');
			try{
			$this->maniaControl->client->setModeScriptSettings(array('S_MapPointsLimit' => (int)($this->lastPointLimit)));
			}catch(FaultException $e){
			}
		}
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
?>
