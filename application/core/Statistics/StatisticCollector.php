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

class StatisticCollector implements CallbackListener {
	/**
	 * Constants
	 */
	const SETTING_COLLECT_STATS_ENABLED    = 'Collect Stats Enabled';
	const SETTING_COLLECT_STATS_MINPLAYERS = 'Minimum Playercount for Collecting Stats';

	/*
	 * Statistics
	 */
	const STAT_ON_SHOOT                  = 'onShoot';
	const STAT_ON_NEARMISS               = 'onNearMiss';
	const STAT_ON_CAPTURE                = 'onCapture';
	const STAT_ON_HIT                    = 'onHit';
	const STAT_ON_GOT_HIT                = 'onGotHit';
	const STAT_ON_DEATH                  = 'onDeath';
	const STAT_ON_PLAYER_REQUEST_RESPAWN = 'onPlayerRequestRespawn';
	const STAT_ON_KILL                   = 'onKill';
	/**
	 * Private Properties
	 */
	private $maniaControl = null;

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

		//Initialize Settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_COLLECT_STATS_ENABLED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_COLLECT_STATS_MINPLAYERS, 4);
	}

	/**
	 * onInit
	 *
	 * @param array $callback
	 */
	public function onInit(array $callback) {
		//Define Stats MetaData
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
		$serverLogin  = $this->maniaControl->server->getLogin();


		switch($callbackName) {
			case 'LibXmlRpc_OnShoot': //TODO
				break;
			case 'LibXmlRpc_OnHit':
				$shooter = $this->maniaControl->playerManager->getPlayer($callback[1][0]);
				$victim  = $this->maniaControl->playerManager->getPlayer($callback[1][1]);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_HIT, $shooter, $serverLogin);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_GOT_HIT, $victim, $serverLogin);
				break;
			case 'LibXmlRpc_OnNearMiss':
				$player = $this->maniaControl->playerManager->getPlayer($callback[1][0]);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_NEARMISS, $player, $serverLogin);
				break;
			case 'LibXmlRpc_OnCapture':
				$player = $this->maniaControl->playerManager->getPlayer($callback[1][0]);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_CAPTURE, $player, $serverLogin);
				break;
			case 'LibXmlRpc_OnArmorEmpty':
				$shooter = $this->maniaControl->playerManager->getPlayer($callback[1][0]);
				$victim  = $this->maniaControl->playerManager->getPlayer($callback[1][1]);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_KILL, $shooter, $serverLogin);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_DEATH, $victim, $serverLogin);
				break;
			case 'LibXmlRpc_OnPlayerRequestRespawn':
				$player = $this->maniaControl->playerManager->getPlayer($callback[1][0]);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_PLAYER_REQUEST_RESPAWN, $player, $serverLogin);
				break;
			case 'OnShoot': //TODO
				break;
			case 'OnNearMiss':
				$paramsObject = json_decode($callback[1][1]);
				$player       = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Shooter->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_NEARMISS, $player, $serverLogin);
				break;
			case 'OnCapture':
				$paramsObject = json_decode($callback[1][1]);
				$player       = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Player->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_CAPTURE, $player, $serverLogin);
				break;
			case 'OnHit':
				$paramsObject = json_decode($callback[1][1]);
				$shooter      = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Shooter->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_HIT, $shooter, $serverLogin);
				$victim = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Victim->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_GOT_HIT, $victim, $serverLogin);
				break;
			case 'OnArmorEmpty':
				$paramsObject = json_decode($callback[1][1]);
				$victim       = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Victim->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_DEATH, $victim, $serverLogin);
				$shooter = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Shooter->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_KILL, $shooter, $serverLogin);
				break;
			case 'OnRequestRespawn':
				$paramsObject = json_decode($callback[1][1]);
				$player       = $this->maniaControl->playerManager->getPlayer($paramsObject->Event->Player->Login);
				$this->maniaControl->statisticManager->incrementStat(self::STAT_ON_PLAYER_REQUEST_RESPAWN, $player, $serverLogin);
				break;
		}
	}
} 