<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;

/**
 * Class reports Usage
 *
 * @author steeffeen & kremsy
 */
class UsageReporter implements TimerListener {
	/**
	 * Constants
	 */
	const UPDATE_MINUTE_COUNT             = 10;
	const SETTING_DISABLE_USAGE_REPORTING = 'Disable Usage Reporting';
	/**
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new Server Settings Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		//TODO setting

		$this->maniaControl->timerManager->registerTimerListening($this, 'reportUsage', 1000 * 60 * self::UPDATE_MINUTE_COUNT);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DISABLE_USAGE_REPORTING, false);
	}

	/**
	 * Reports Usage every xx Minutes
	 *
	 * @param $time
	 */
	public function reportUsage($time) {
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_DISABLE_USAGE_REPORTING)) {
			return;
		}

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

		//TODO make on website
	/*	$this->maniaControl->fileReader->loadFile("url/webservice?info=" . $info, function ($response, $error) {
				if ($error) {
					$this->maniaControl->log("Error while Sending data: " . $error);
				}
			});*/
	}
} 