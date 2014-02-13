<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Update\UpdateManager;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

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

		$properties                        = array();
		$properties['ManiaControlVersion'] = ManiaControl::VERSION;
		$properties['OperatingSystem']     = php_uname();
		$properties['PHPVersion']          = phpversion();
		$properties['ServerLogin']         = $this->maniaControl->server->login;
		$properties['TitleId']             = $this->maniaControl->server->titleId;
		$properties['ServerName']          = Formatter::stripDirtyCodes($this->maniaControl->server->getName());
		$properties['PlayerCount']         = $this->maniaControl->playerManager->getPlayerCount();

		$maxPlayers               = $this->maniaControl->client->getMaxPlayers();
		$properties['MaxPlayers'] = $maxPlayers["CurrentValue"];

		try {
			$scriptName               = $this->maniaControl->client->getScriptName();
			$properties['ScriptName'] = $scriptName["CurrentValue"];
		} catch(Exception $e) {
			if ($e->getMessage() == 'Not in script mode.') {
				$properties['ScriptName'] = '';
			} else {
				throw $e;
			}
		}

		$json = json_encode($properties);
		$info = base64_encode($json);

		$this->maniaControl->fileReader->loadFile(UpdateManager::URL_WEBSERVICE . "/usagereport?info=" . urlencode($info), function ($response, $error) {
			$response = json_decode($response);
			if ($error || !$response) {
				$this->maniaControl->log("Error while Sending data: " . $error);
			}
		});
	}
} 