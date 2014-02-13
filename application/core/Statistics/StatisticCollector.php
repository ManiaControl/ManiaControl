<?php
/**
 * Statistic Collector Class
 *
 * @author steeffeen & kremsy
 */
namespace ManiaControl\Statistics;


use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\PlayerManager;

class StatisticCollector implements CallbackListener {
	/**
	 * Constants
	 */
	const SETTING_COLLECT_STATS_ENABLED    = 'Collect Stats Enabled';
	const SETTING_COLLECT_STATS_MINPLAYERS = 'Minimum Playercount for Collecting Stats';
	const SETTING_ON_SHOOT_PRESTORE        = 'Prestore Shoots before insert into Database';
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

	const WEAPON_LASER   = 1;
	const WEAPON_ROCKET  = 2;
	const WEAPON_NUCLEUS = 3;
	const WEAPON_ARROW   = 5;

	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $onShootArray = array();


	/**
	 * Construct player manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) { //TODO statistic wins
		$this->maniaControl = $maniaControl;

		//Register Callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACK, $this, 'handleCallbacks');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACKARRAY, $this, 'handleCallbacks');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'onInit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECTED, $this, 'onPlayerDisconnect');

		//Initialize Settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_COLLECT_STATS_ENABLED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_COLLECT_STATS_MINPLAYERS, 2); //TODO just temp on 2
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_ON_SHOOT_PRESTORE, 10);
	}

	/**
	 * onInit
	 *
	 * @param array $callback
	 */
	public function onInit(array $callback) {
		//Define Stats MetaData
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_PLAYTIME, StatisticManager::STAT_TYPE_TIME);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_MAP_WINS);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_SHOOT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_NEARMISS);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_CAPTURE);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_HIT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_GOT_HIT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_DEATH);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_PLAYER_REQUEST_RESPAWN);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_KILL);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_LASER_HIT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_LASER_SHOT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_NUCLEUS_HIT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_NUCLEUS_SHOT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ROCKET_HIT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ROCKET_SHOT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ARROW_HIT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ARROW_SHOT);
	}

	/**
	 * Handle EndMap
	 *
	 * @param array $callback
	 */
	public function onEndMap(array $callback) {
		//Check for Minimum PlayerCount
		if (count($this->maniaControl->playerManager->getPlayers()) < $this->maniaControl->settingManager->getSetting($this, self::SETTING_COLLECT_STATS_MINPLAYERS)) {
			return;
		}

		$leaders = $this->maniaControl->server->rankingManager->getLeaders();

		foreach($leaders as $leaderLogin) {
			$leader = $this->maniaControl->playerManager->getPlayer($leaderLogin);
			$this->maniaControl->statisticManager->incrementStat(self::STAT_MAP_WINS, $leader);
		}
	}

	/**
	 * Handle Player Shoots
	 *
	 * @param $login
	 */
	private function handleOnShoot($login, $weaponNumber) {
		if (!isset($this->onShootArray[$login])) {
			$this->onShootArray[$login] = array(self::WEAPON_ROCKET => 0, self::WEAPON_ARROW => 0, self::WEAPON_NUCLEUS => 0, self::WEAPON_LASER => 0);
			$this->onShootArray[$login][$weaponNumber]++;
		} else {
			$this->onShootArray[$login][$weaponNumber]++;
		}

		//Write Shoot Data into database
		if (array_sum($this->onShootArray[$login]) > $this->maniaControl->settingManager->getSetting($this, self::SETTING_ON_SHOOT_PRESTORE)) {
			$player = $this->maniaControl->playerManager->getPlayer($login);

			$rocketShots  = $this->onShootArray[$login][self::WEAPON_ROCKET];
			$laserShots   = $this->onShootArray[$login][self::WEAPON_LASER];
			$arrowShots   = $this->onShootArray[$login][self::WEAPON_ARROW];
			$nucleusShots = $this->onShootArray[$login][self::WEAPON_NUCLEUS];

			if ($rocketShots > 0) {
				$this->maniaControl->statisticManager->insertStat(self::STAT_ROCKET_SHOT, $player, $this->maniaControl->server->index, $rocketShots);
				$this->onShootArray[$login][self::WEAPON_ROCKET] = 0;
			}
			if ($laserShots > 0) {
				$this->maniaControl->statisticManager->insertStat(self::STAT_LASER_SHOT, $player, $this->maniaControl->server->index, $laserShots);
				$this->onShootArray[$login][self::WEAPON_LASER] = 0;
			}
			if ($arrowShots > 0) {
				$this->maniaControl->statisticManager->insertStat(self::STAT_ARROW_SHOT, $player, $this->maniaControl->server->index, $arrowShots);
				$this->onShootArray[$login][self::WEAPON_ARROW] = 0;
			}
			if ($nucleusShots > 0) {
				$this->maniaControl->statisticManager->insertStat(self::STAT_NUCLEUS_SHOT, $player, $this->maniaControl->server->index, $nucleusShots);
				$this->onShootArray[$login][self::WEAPON_NUCLEUS] = 0;
			}

			$this->maniaControl->statisticManager->insertStat(self::STAT_ON_SHOOT, $player, $this->maniaControl->server->index, $rocketShots + $laserShots + $arrowShots + $nucleusShots);
		}
	}

	/**
	 * Gets the Weapon stat
	 *
	 * @param $weaponNumber
	 * @return string
	 */
	private function getWeaponStat($weaponNumber, $shot = true) {
		if ($shot) {
			switch($weaponNumber) {
				case self::WEAPON_ROCKET:
					return self::STAT_ROCKET_SHOT;
				case self::WEAPON_LASER:
					return self::STAT_LASER_SHOT;
				case self::WEAPON_ARROW:
					return self::STAT_ARROW_SHOT;
				case self::WEAPON_NUCLEUS:
					return self::STAT_NUCLEUS_SHOT;
				default:
					return -1;
			}
		} else {
			switch($weaponNumber) {
				case self::WEAPON_ROCKET:
					return self::STAT_ROCKET_HIT;
				case self::WEAPON_LASER:
					return self::STAT_LASER_HIT;
				case self::WEAPON_ARROW:
					return self::STAT_ARROW_HIT;
				case self::WEAPON_NUCLEUS:
					return self::STAT_NUCLEUS_HIT;
				default:
					return -1;
			}
		}
	}

	/**
	 * Insert OnShoot Statistic when a player leaves
	 *
	 * @param array $callback
	 */
	public function onPlayerDisconnect(array $callback) {
		$player = $callback[1];

		//Check if Stat Collecting is enabled
		if (!$this->maniaControl->settingManager->getSetting($this, self::SETTING_COLLECT_STATS_ENABLED)) {
			return;
		}

		//Insert Data into Database, and destroy player
		if (isset($this->onShootArray[$player->login])) {
			if ($this->onShootArray[$player->login] > 0) {
				$this->maniaControl->statisticManager->insertStat(self::STAT_ON_SHOOT, $player, $this->maniaControl->server->index, $this->onShootArray[$player->login]);
			}
			unset($this->onShootArray[$player->login]);
		}
	}

	/**
	 * Handle stats on callbacks
	 *
	 * @param array $callback
	 */
	public function handleCallbacks(array $callback) { //TODO survivals
		//Check if Stat Collecting is enabled
		if (!$this->maniaControl->settingManager->getSetting($this, self::SETTING_COLLECT_STATS_ENABLED)) {
			return;
		}

		//Check for Minimum PlayerCount
		if (count($this->maniaControl->playerManager->getPlayers()) < $this->maniaControl->settingManager->getSetting($this, self::SETTING_COLLECT_STATS_MINPLAYERS)) {
			return;
		}

		$callbackName = $callback[1][0];

		switch($callbackName) {
			case 'LibXmlRpc_OnShoot':
				$this->handleOnShoot($callback[1][1][0], $callback[1][1][1]);
				break;
			case 'LibXmlRpc_OnHit':
				$shooter = $this->maniaControl->playerManager->getPlayer($callback[1][1][0]);
				$victim  = $this->maniaControl->playerManager->getPlayer($callback[1][1][1]);
				$weapon  = $callback[1][1][3];
				$this->maniaControl->statisticManager->incrementStat($this->getWeaponStat(intval($weapon), false), $shooter);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_HIT, $shooter);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_GOT_HIT, $victim);
				break;
			case 'LibXmlRpc_OnNearMiss':
				$player = $this->maniaControl->playerManager->getPlayer($callback[1][1][0]);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_NEARMISS, $player);
				break;
			case 'LibXmlRpc_OnCapture':
				$logins = $callback[1][1][0];
				$logins = explode(';', $logins);
				foreach($logins as $login) {
					$player = $this->maniaControl->playerManager->getPlayer($login);
					if (!$player) {
						continue;
					}
					$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_CAPTURE, $player);
				}
				break;
			case 'LibXmlRpc_OnArmorEmpty':
				$shooter = $this->maniaControl->playerManager->getPlayer($callback[1][1][0]);
				$victim  = $this->maniaControl->playerManager->getPlayer($callback[1][1][1]);
				if ($shooter != null) {
					$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_KILL, $shooter);
				}
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_DEATH, $victim);
				break;
			case 'LibXmlRpc_OnPlayerRequestRespawn':
				$player = $this->maniaControl->playerManager->getPlayer($callback[1][1][0]);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_PLAYER_REQUEST_RESPAWN, $player);
				break;
			case 'OnShoot':
				$paramsObject = json_decode($callback[1][1]);
				$this->handleOnShoot($paramsObject->Event->Shooter->Login, $paramsObject->Event->WeaponNum);
				break;
			case 'OnNearMiss':
				$paramsObject = json_decode($callback[1][1]);
				$player       = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Shooter->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_NEARMISS, $player);
				break;
			case 'OnCapture':
				$paramsObject = json_decode($callback[1][1]);
				$player       = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Player->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_CAPTURE, $player);
				break;
			case 'OnHit':
				$paramsObject = json_decode($callback[1][1]);
				$shooter      = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Shooter->Login);
				$victim       = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Victim->Login);
				$weapon       = $paramsObject->Event->WeaponNum;
				$this->maniaControl->statisticManager->incrementStat($this->getWeaponStat($weapon, false), $shooter);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_HIT, $shooter);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_GOT_HIT, $victim);
				break;
			case 'OnArmorEmpty':
				$paramsObject = json_decode($callback[1][1]);
				$victim       = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Victim->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_DEATH, $victim);
				if (isset($paramsObject->Event->Shooter->Login)) {
					$shooter = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Shooter->Login);
					$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_KILL, $shooter);
				}
				break;
			case 'OnRequestRespawn':
				$paramsObject = json_decode($callback[1][1]);
				$player       = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Player->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_PLAYER_REQUEST_RESPAWN, $player);
				break;
			case 'EndTurn': //TODO make it for other modes working
				$paramsObject = json_decode($callback[1][1]);
				$durationTime = (int)(($paramsObject->EndTime - $paramsObject->StartTime) / 1000);
				$scoresTable  = $paramsObject->ScoresTable;
				foreach($scoresTable as $score) {
					$player = $this->maniaControl->playerManager->getPlayer($score->Login);
					$this->maniaControl->statisticManager->insertStat(self::STAT_PLAYTIME, $player, -1, $durationTime);
				}
				break;
		}
	}
} 