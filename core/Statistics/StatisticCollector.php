<?php

namespace ManiaControl\Statistics;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\Callbacks\Structures\ManiaPlanet\StartEndStructure;
use ManiaControl\Callbacks\Structures\ShootMania\Models\Weapons;
use ManiaControl\Callbacks\Structures\ShootMania\OnArmorEmptyStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnCaptureStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnHitStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnNearMissStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnPlayerRequestRespawnStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnShootStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Statistic Collector Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class StatisticCollector implements CallbackListener { //TODO remove old callbacks later
	/*
	 * Constants
	 */
	const SETTING_COLLECT_STATS_ENABLED    = 'Collect Stats Enabled';
	const SETTING_COLLECT_STATS_MINPLAYERS = 'Minimum Player Count for Collecting Stats';
	const SETTING_ON_SHOOT_PRESTORE        = 'Prestore Shots before Insert into Database';
	/*
	 * Statistics
	 */
	const STAT_PLAYTIME                  = 'Play Time';
	const STAT_MAP_WINS                  = 'Map Wins';
	const STAT_ON_SHOOT                  = 'Shots';
	const STAT_ON_NEARMISS               = 'Near Misses';
	const STAT_ON_CAPTURE                = 'Captures';
	const STAT_ON_HIT                    = 'Hits';
	const STAT_ON_GOT_HIT                = 'Got Hits';
	const STAT_ON_DEATH                  = 'Deaths';
	const STAT_ON_PLAYER_REQUEST_RESPAWN = 'Respawns';
	const STAT_ON_KILL                   = 'Kills';
	const STAT_LASER_SHOT                = 'Laser Shots';
	const STAT_LASER_HIT                 = 'Laser Hits';
	const STAT_ROCKET_SHOT               = 'Rocket Shots';
	const STAT_ROCKET_HIT                = 'Rocket Hits';
	const STAT_ARROW_SHOT                = 'Arrow Shots';
	const STAT_ARROW_HIT                 = 'Arrow Hits';
	const STAT_NUCLEUS_SHOT              = 'Nucleus Shots';
	const STAT_NUCLEUS_HIT               = 'Nucleus Hits';

	const SPECIAL_STAT_KILL_DEATH_RATIO = 'Kill / Death';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $onShootArray = array();

	private $startPlayLoopTime = -1;

	/**
	 * Construct a new statistic collector instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACK, $this, 'handleCallbacks');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACKARRAY, $this, 'handleCallbacks');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'onInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'onPlayerDisconnect');

		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::SM_ONHIT, $this, 'onHitCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::SM_ONSHOOT, $this, 'onShootCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::SM_ONNEARMISS, $this, 'onNearMissCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::SM_ONCAPTURE, $this, 'onCaptureCallback');

		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_STARTPLAYLOOP, $this, 'onStartPlayLoop');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_ENDPLAYLOOP, $this, 'onEndPlayLoop');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::SM_ONPLAYERREMOVED, $this, 'onPlayerRemoved');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONPLAYERREMOVED, $this, 'onPlayerRemoved');

		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::SM_ONCAPTURE, $this, 'onCaptureCallback');


		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_COLLECT_STATS_ENABLED, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_COLLECT_STATS_MINPLAYERS, 4);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_ON_SHOOT_PRESTORE, 10);
	}

	/**
	 * Handle ManiaControl OnInit Callback
	 *
	 * @internal
	 */
	public function onInit() {
		// Define Stats MetaData
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_PLAYTIME, StatisticManager::STAT_TYPE_TIME);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_MAP_WINS);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ON_SHOOT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ON_NEARMISS);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ON_CAPTURE);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ON_HIT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ON_GOT_HIT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ON_DEATH);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ON_PLAYER_REQUEST_RESPAWN);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ON_KILL);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_LASER_HIT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_LASER_SHOT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_NUCLEUS_HIT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_NUCLEUS_SHOT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ROCKET_HIT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ROCKET_SHOT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ARROW_HIT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_ARROW_SHOT);
	}

	/**
	 * Checks if the Collecting is Enabled
	 *
	 * @api
	 * @return boolean
	 */
	public function isCollectingEnabled() {
		return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLLECT_STATS_ENABLED);
	}


	/**
	 * Check for the Minimum Amount of Players to collect
	 *
	 * @api
	 * @return bool
	 */
	public function checkForMinimumPlayers() {
		// Check for Minimum PlayerCount
		return ($this->maniaControl->getPlayerManager()->getPlayerCount() < $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLLECT_STATS_MINPLAYERS));
	}

	/**
	 * Handles the onHitCallback
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\ShootMania\OnHitStructure $structure
	 */
	public function onHitCallback(OnHitStructure $structure) {
		if (!$this->isCollectingEnabled() || !$this->checkForMinimumPlayers()) {
			return;
		}

		if ($structure->getShooter()) {
			$this->maniaControl->getStatisticManager()->incrementStat($this->getWeaponStat($structure->getWeapon(), false), $structure->getShooter());
			$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_HIT, $structure->getShooter());
		}
		if ($structure->getVictim()) {
			$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_GOT_HIT, $structure->getVictim());
		}
	}

	/**
	 * Handles the onShoot Callback
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\ShootMania\OnShootStructure $structure
	 */
	public function onShootCallback(OnShootStructure $structure) {
		if (!$this->isCollectingEnabled() || !$this->checkForMinimumPlayers()) {
			return;
		}

		$this->handleOnShoot($structure->getShooter()->login, $structure->getWeapon());
	}

	/**
	 * Handles the OnNearMiss Callback
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\ShootMania\OnNearMissStructure $structure
	 */
	public function onNearMissCallback(OnNearMissStructure $structure) {
		if (!$this->isCollectingEnabled() || !$this->checkForMinimumPlayers()) {
			return;
		}

		$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_NEARMISS, $structure->getShooter());
	}


	/**
	 * Handles the OnCapture Callback
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\ShootMania\OnCaptureStructure $structure
	 */
	public function onCaptureCallback(OnCaptureStructure $structure) {
		if (!$this->isCollectingEnabled() || !$this->checkForMinimumPlayers()) {
			return;
		}

		foreach ($structure->getPlayerArray() as $player) {
			$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_CAPTURE, $player);
		}
	}

	/**
	 * Handles the OnArmorEmpty Callback
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\ShootMania\OnArmorEmptyStructure $structure
	 */
	public function onArmorEmptyCallback(OnArmorEmptyStructure $structure) {
		if (!$this->isCollectingEnabled() || !$this->checkForMinimumPlayers()) {
			return;
		}
		if ($structure->getShooter()) {
			$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_KILL, $structure->getShooter());
		}
		if ($structure->getVictim()) {
			$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_DEATH, $structure->getVictim());
		}
	}

	/**
	 * Handles the OnPlayerRequestRespawn Callback
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\ShootMania\OnPlayerRequestRespawnStructure $structure
	 */
	public function onPlayerRequestRespawnCallback(OnPlayerRequestRespawnStructure $structure) {
		if (!$this->isCollectingEnabled() || !$this->checkForMinimumPlayers()) {
			return;
		}

		$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_PLAYER_REQUEST_RESPAWN, $structure->getPlayer());
	}


	/**
	 * Handle EndMap
	 *
	 * @internal
	 * @param array $callback
	 */
	public function onEndMap(array $callback) {
		//Check for Minimum PlayerCount
		if ($this->maniaControl->getPlayerManager()->getPlayerCount() < $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLLECT_STATS_MINPLAYERS)) {
			return;
		}

		$leaders = $this->maniaControl->getServer()->getRankingManager()->getLeaders();

		foreach ($leaders as $leaderLogin) {
			$leader = $this->maniaControl->getPlayerManager()->getPlayer($leaderLogin);
			$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_MAP_WINS, $leader);
		}
	}

	/**
	 * Insert OnShoot Statistic when a player leaves
	 *
	 * @internal
	 * @param Player $player
	 */
	public function onPlayerDisconnect(Player $player) {
		if (!$this->isCollectingEnabled() || !$this->checkForMinimumPlayers()) {
			return;
		}

		// Insert Data into Database, and destroy player
		if (isset($this->onShootArray[$player->login])) {
			if ($this->onShootArray[$player->login] > 0) {
				$this->maniaControl->getStatisticManager()->insertStat(self::STAT_ON_SHOOT, $player, $this->maniaControl->getServer()->index, $this->onShootArray[$player->login]);
			}
			unset($this->onShootArray[$player->login]);
		}

	}

	/**
	 * Update PlayTime if the Player leaves
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure $structure
	 */
	public function onPlayerRemoved(BasePlayerTimeStructure $structure) {
		if (!$this->isCollectingEnabled() || !$this->checkForMinimumPlayers()) {
			return;
		}

		//Check if in a PlayLoop actually has been started
		if ($this->startPlayLoopTime < 0) {
			return;
		}

		$durationTime = ($structure->getTime() - $this->startPlayLoopTime) / 1000;

		//TODO reverify why player can be 0
		if($structure->getPlayer()){
			$this->maniaControl->getStatisticManager()->insertStat(self::STAT_PLAYTIME, $structure->getPlayer(), $this->maniaControl->getServer()->index, $durationTime);
		}
	}


	/**
	 * Handles the PlayerTime on Join
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\ManiaPlanet\StartEndStructure $structure
	 */
	public function onStartPlayLoop(StartEndStructure $structure) {
		$this->startPlayLoopTime = $structure->getTime();
	}

	/**
	 * Handles The Playtime Statistic on EndPlayerLoop
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\ManiaPlanet\StartEndStructure $structure
	 */
	public function onEndPlayLoop(StartEndStructure $structure) {
		// Check if Stat Collecting is enabled
		if (!$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLLECT_STATS_ENABLED)) {
			return;
		}

		//Check if in a PlayLoop actually has been started
		if ($this->startPlayLoopTime < 0) {
			return;
		}

		$durationTime = ($structure->getTime() - $this->startPlayLoopTime) / 1000;

		foreach ($this->maniaControl->getPlayerManager()->getPlayers() as $player) {
			$this->maniaControl->getStatisticManager()->insertStat(self::STAT_PLAYTIME, $player, $this->maniaControl->getServer()->index, $durationTime);
		}

		$this->startPlayLoopTime = -1;
	}

	/**
	 * Handle stats on callbacks
	 *
	 * @deprecated
	 * @internal
	 * @param array $callback
	 */
	public function handleCallbacks(array $callback) {
		//TODO remove later, only used for MP3
		//TODO survivals
		// Check if Stat Collecting is enabled
		if (!$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLLECT_STATS_ENABLED)) {
			return;
		}

		// Check for Minimum PlayerCount
		if ($this->maniaControl->getPlayerManager()->getPlayerCount() < $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_COLLECT_STATS_MINPLAYERS)) {
			return;
		}

		$callbackName = $callback[1][0];

		//TODO remove later
		switch ($callbackName) {
			case 'LibXmlRpc_OnShoot':
				$this->handleOnShoot($callback[1][1][0], $callback[1][1][1]);
				break;
			case 'LibXmlRpc_OnHit':
				$shooter = $this->maniaControl->getPlayerManager()->getPlayer($callback[1][1][0]);
				$victim  = $this->maniaControl->getPlayerManager()->getPlayer($callback[1][1][1]);
				$weapon  = $callback[1][1][3];
				if ($shooter) {
					$this->maniaControl->getStatisticManager()->incrementStat($this->getWeaponStat(intval($weapon), false), $shooter);
					$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_HIT, $shooter);
				}
				if ($victim) {
					$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_GOT_HIT, $victim);
				}
				break;
			case 'LibXmlRpc_OnNearMiss':
				$player = $this->maniaControl->getPlayerManager()->getPlayer($callback[1][1][0]);
				$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_NEARMISS, $player);
				break;
			case 'LibXmlRpc_OnCapture':
				$logins = $callback[1][1][0];
				$logins = explode(';', $logins);
				foreach ($logins as $login) {
					$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
					if (!$player) {
						continue;
					}
					$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_CAPTURE, $player);
				}
				break;
			case 'LibXmlRpc_OnArmorEmpty':
				$victim = $this->maniaControl->getPlayerManager()->getPlayer($callback[1][1][1]);
				if (isset($callback[1][1][0])) {
					$shooter = $this->maniaControl->getPlayerManager()->getPlayer($callback[1][1][0]);
					if ($shooter) {
						$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_KILL, $shooter);
					}
				}
				if ($victim) {
					$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_DEATH, $victim);
				}
				break;
			case 'LibXmlRpc_OnPlayerRequestRespawn':
				$player = $this->maniaControl->getPlayerManager()->getPlayer($callback[1][1][0]);
				$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_PLAYER_REQUEST_RESPAWN, $player);
				break;
			case 'OnShoot':
				$paramsObject = json_decode($callback[1][1]);
				if ($paramsObject && isset($paramsObject->Event)) {
					$this->handleOnShoot($paramsObject->Event->Shooter->Login, $paramsObject->Event->WeaponNum);
				}
				break;
			case 'OnNearMiss':
				$paramsObject = json_decode($callback[1][1]);
				if ($paramsObject && isset($paramsObject->Event)) {
					$player = $this->maniaControl->getPlayerManager()->getPlayer($paramsObject->Event->Shooter->Login);
					$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_NEARMISS, $player);
				}
				break;
			case 'OnCapture':
				$paramsObject = json_decode($callback[1][1]);
				if ($paramsObject && isset($paramsObject->Event)) {
					$player = $this->maniaControl->getPlayerManager()->getPlayer($paramsObject->Event->Player->Login);
					$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_CAPTURE, $player);
				}
				break;
			case 'OnHit':
				$paramsObject = json_decode($callback[1][1]);
				if ($paramsObject && isset($paramsObject->Event)) {
					$weapon = (int) $paramsObject->Event->WeaponNum;
					if (isset($paramsObject->Event->Shooter)) {
						$shooter = $this->maniaControl->getPlayerManager()->getPlayer($paramsObject->Event->Shooter->Login);
						if ($shooter) {
							$this->maniaControl->getStatisticManager()->incrementStat($this->getWeaponStat($weapon, false), $shooter);
							$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_HIT, $shooter);
						}
					}
					if (isset($paramsObject->Event->Victim)) {
						$victim = $this->maniaControl->getPlayerManager()->getPlayer($paramsObject->Event->Victim->Login);
						if ($victim) {
							$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_GOT_HIT, $victim);
						}
					}
				}
				break;
			case 'OnArmorEmpty':
				$paramsObject = json_decode($callback[1][1]);
				if ($paramsObject && isset($paramsObject->Event)) {
					$victim = $this->maniaControl->getPlayerManager()->getPlayer($paramsObject->Event->Victim->Login);
					$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_DEATH, $victim);
					if (isset($paramsObject->Event->Shooter->Login)) {
						$shooter = $this->maniaControl->getPlayerManager()->getPlayer($paramsObject->Event->Shooter->Login);
						if ($shooter) {
							$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_KILL, $shooter);
						}
					}
				}
				break;
			case 'OnRequestRespawn':
				$paramsObject = json_decode($callback[1][1]);
				if ($paramsObject && isset($paramsObject->Event)) {
					$player = $this->maniaControl->getPlayerManager()->getPlayer($paramsObject->Event->Player->Login);
					$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_ON_PLAYER_REQUEST_RESPAWN, $player);
				}
				break;
			case 'EndTurn': //TODO make it for other modes working //TODO also not available in MP4 atm
				$paramsObject = json_decode($callback[1][1]);
				if ($paramsObject && is_array($paramsObject->ScoresTable)) {
					$durationTime = (int) (($paramsObject->EndTime - $paramsObject->StartTime) / 1000);
					foreach ($paramsObject->ScoresTable as $score) {
						$player = $this->maniaControl->getPlayerManager()->getPlayer($score->Login);
						$this->maniaControl->getStatisticManager()->insertStat(self::STAT_PLAYTIME, $player, -1, $durationTime);
					}
				}
				break;
		}
	}

	/**
	 * Handle Player Shots
	 *
	 * @param string $login
	 * @param int    $weaponNumber
	 */
	private function handleOnShoot($login, $weaponNumber) {
		//TODO update to player in MP4
		if (!isset($this->onShootArray[$login])) {
			$this->onShootArray[$login] = array(Weapons::ROCKET => 0, Weapons::ARROW => 0, Weapons::NUCLEUS => 0, Weapons::LASER => 0);
		}
		if (!isset($this->onShootArray[$login][$weaponNumber])) {
			$this->onShootArray[$login][$weaponNumber] = 0;
		}
		$this->onShootArray[$login][$weaponNumber]++;

		//Write Shoot Data into database
		if (array_sum($this->onShootArray[$login]) > $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_ON_SHOOT_PRESTORE)) {
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);

			$rocketShots  = $this->onShootArray[$login][Weapons::ROCKET];
			$laserShots   = $this->onShootArray[$login][Weapons::LASER];
			$arrowShots   = $this->onShootArray[$login][Weapons::ARROW];
			$nucleusShots = $this->onShootArray[$login][Weapons::NUCLEUS];

			if ($rocketShots > 0) {
				$this->maniaControl->getStatisticManager()->insertStat(self::STAT_ROCKET_SHOT, $player, $this->maniaControl->getServer()->index, $rocketShots);
				$this->onShootArray[$login][Weapons::ROCKET] = 0;
			}
			if ($laserShots > 0) {
				$this->maniaControl->getStatisticManager()->insertStat(self::STAT_LASER_SHOT, $player, $this->maniaControl->getServer()->index, $laserShots);
				$this->onShootArray[$login][Weapons::LASER] = 0;
			}
			if ($arrowShots > 0) {
				$this->maniaControl->getStatisticManager()->insertStat(self::STAT_ARROW_SHOT, $player, $this->maniaControl->getServer()->index, $arrowShots);
				$this->onShootArray[$login][Weapons::ARROW] = 0;
			}
			if ($nucleusShots > 0) {
				$this->maniaControl->getStatisticManager()->insertStat(self::STAT_NUCLEUS_SHOT, $player, $this->maniaControl->getServer()->index, $nucleusShots);
				$this->onShootArray[$login][Weapons::NUCLEUS] = 0;
			}

			$this->maniaControl->getStatisticManager()->insertStat(self::STAT_ON_SHOOT, $player, $this->maniaControl->getServer()->index, $rocketShots + $laserShots + $arrowShots + $nucleusShots);
		}
	}

	/**
	 * Get the Weapon stat
	 *
	 * @param int  $weaponNumber
	 * @param bool $shot
	 * @return string
	 */
	private function getWeaponStat($weaponNumber, $shot = true) {
		if ($shot) {
			switch ($weaponNumber) {
				case Weapons::ROCKET:
					return self::STAT_ROCKET_SHOT;
				case Weapons::LASER:
					return self::STAT_LASER_SHOT;
				case Weapons::ARROW:
					return self::STAT_ARROW_SHOT;
				case Weapons::NUCLEUS:
					return self::STAT_NUCLEUS_SHOT;
				default:
					return -1;
			}
		} else {
			switch ($weaponNumber) {
				case Weapons::ROCKET:
					return self::STAT_ROCKET_HIT;
				case Weapons::LASER:
					return self::STAT_LASER_HIT;
				case Weapons::ARROW:
					return self::STAT_ARROW_HIT;
				case Weapons::NUCLEUS:
					return self::STAT_NUCLEUS_HIT;
				default:
					return -1;
			}
		}
	}
} 