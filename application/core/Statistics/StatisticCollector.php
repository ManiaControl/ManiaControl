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
	const STAT_ON_SHOOT                  = 'Shots';
	const STAT_ON_NEARMISS               = 'Near Misses';
	const STAT_ON_CAPTURE                = 'Captures';
	const STAT_ON_HIT                    = 'Hits';
	const STAT_ON_GOT_HIT                = 'Got Hits';
	const STAT_ON_DEATH                  = 'Deaths';
	const STAT_ON_PLAYER_REQUEST_RESPAWN = 'Respawns';
	const STAT_ON_KILL                   = 'Kills';

	const SPECIAL_STAT_KILL_DEATH_RATIO = 'Kill / Death';

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
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		//Register Callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACK, $this, 'handleCallbacks');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACKARRAY, $this, 'handleCallbacks');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'onInit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECTED, $this, 'onPlayerDisconnect');

		//Initialize Settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_COLLECT_STATS_ENABLED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_COLLECT_STATS_MINPLAYERS, 1); //TODO TEMP on 1, normally 3 or 4
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_ON_SHOOT_PRESTORE, 30);
	}

	/**
	 * onInit
	 *
	 * @param array $callback
	 */
	public function onInit(array $callback) {
		//Define Stats MetaData
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_PLAYTIME, StatisticManager::STAT_TYPE_TIME);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_SHOOT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_NEARMISS);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_CAPTURE);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_HIT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_GOT_HIT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_DEATH);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_PLAYER_REQUEST_RESPAWN);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_KILL);
	}

	/**
	 * Handle Player Shoots
	 *
	 * @param $login
	 */
	private function handleOnShoot($login) {
		if(!isset($this->onShootArray[$login])) {
			$this->onShootArray[$login] = 1;
		} else {
			$this->onShootArray[$login]++;
		}

		//Write Shoot Data into database
		if($this->onShootArray[$login] > $this->maniaControl->settingManager->getSetting($this, self::SETTING_ON_SHOOT_PRESTORE)) {
			$player = $this->maniaControl->playerManager->getPlayer($login);
			$this->maniaControl->statisticManager->insertStat(self::STAT_ON_SHOOT, $player, $this->maniaControl->server->index, $this->onShootArray[$login]);
			$this->onShootArray[$login] = 0;
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
		if(!$this->maniaControl->settingManager->getSetting($this, self::SETTING_COLLECT_STATS_ENABLED)) {
			return;
		}

		//Insert Data into Database, and destroy player
		if(isset($this->onShootArray[$player->login])) {
			if($this->onShootArray[$player->login] > 0) {
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
	public function handleCallbacks(array $callback) {
		//Check if Stat Collecting is enabled
		if(!$this->maniaControl->settingManager->getSetting($this, self::SETTING_COLLECT_STATS_ENABLED)) {
			return;
		}

		//Check for Minplayer
		if(count($this->maniaControl->playerManager->getPlayers()) < $this->maniaControl->settingManager->getSetting($this, self::SETTING_COLLECT_STATS_MINPLAYERS)) {
			return;
		}

		$callbackName = $callback[1][0];

		switch($callbackName) {
			case 'LibXmlRpc_OnShoot':
				$this->handleOnShoot($callback[1][1][0]);
				break;
			case 'LibXmlRpc_OnHit':
				$shooter = $this->maniaControl->playerManager->getPlayer($callback[1][1][0]);
				$victim  = $this->maniaControl->playerManager->getPlayer($callback[1][1][1]);
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
					if(!$player) {
						continue;
					}
					$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_CAPTURE, $player);
				}
				break;
			case 'LibXmlRpc_OnArmorEmpty':
				$shooter = $this->maniaControl->playerManager->getPlayer($callback[1][1][0]);
				$victim  = $this->maniaControl->playerManager->getPlayer($callback[1][1][1]);
				if($shooter != null) {
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
				$this->handleOnShoot($paramsObject->Event->Shooter->Login);
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
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_HIT, $shooter);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_GOT_HIT, $victim);
				break;
			case 'OnArmorEmpty':
				$paramsObject = json_decode($callback[1][1]);
				$victim       = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Victim->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_DEATH, $victim);
				$shooter = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Shooter->Login);
				if($shooter != null) {
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