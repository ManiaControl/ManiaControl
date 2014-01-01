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
	const STAT_ON_SHOOT = 'onShoot';

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
	 * @param array $callback
	 */
	public function onInit(array $callback){
		//Define Stats MetaData
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_ON_SHOOT);
	}


	/**
	 * Handle stats on callbacks
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

		var_dump($callback);

	}
} 