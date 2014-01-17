<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;

/**
 * Class reports Usage
 *
 * @author steeffeen & kremsy
 */
class UsageReporter implements CallbackListener {
	/**
	 * Constants
	 */
	const UPDATE_MINUTE_COUNT             = 10;
	const SETTING_DISABLE_USAGE_REPORTING = 'Disable Usage Reporting';
	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $minuteCount = 0;

	/**
	 * Create a new Server Settings Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		//TODO setting
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_1_MINUTE, $this, 'handleEveryMinute');

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DISABLE_USAGE_REPORTING, false);
	}

	public function handleEveryMinute(array $callback) {
		if($this->maniaControl->settingManager->getSetting($this, self::SETTING_DISABLE_USAGE_REPORTING)) {
			return;
		}

		$this->minuteCount++;

		if($this->minuteCount >= self::UPDATE_MINUTE_COUNT) {
			$properties                    = array();
			$properties['MC_Version']      = ManiaControl::VERSION;
			$properties['OperatingSystem'] = php_uname();
			$properties['PHPVersion']      = phpversion();
			$properties['ServerLogin']     = $this->maniaControl->server->login;
			$properties['TitleId']         = $this->maniaControl->server->titleId;
			$properties['ServerName']      = $this->maniaControl->server->getName();
			$properties['PlayerCount']     = count($this->maniaControl->playerManager->getPlayers());
			$properties['MaxPlayers']      = $this->maniaControl->client->getMaxPlayers();

			$json = json_encode($properties);
			$info = base64_encode($json);

			//TODO send Info
			$this->minuteCount = 0;
		}
	}
} 