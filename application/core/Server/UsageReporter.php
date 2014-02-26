<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

/**
 * Class reporting ManiaControl Usage for the Server
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
	 * Create a new Usage Reporter Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DISABLE_USAGE_REPORTING, false);
		$this->maniaControl->timerManager->registerTimerListening($this, 'reportUsage', 1000 * 60 * self::UPDATE_MINUTE_COUNT);

	}

	/**
	 * Reports Usage every xx Minutes
	 *
	 * @param float $time
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
		$properties['ServerName']          = Formatter::stripDirtyCodes($this->maniaControl->client->getServerName());
		$properties['PlayerCount']         = $this->maniaControl->playerManager->getPlayerCount();
		$properties['MemoryUsage']         = memory_get_usage();
		$properties['MemoryPeakUsage']     = memory_get_peak_usage();

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

		$activePlugins = array();

		if(is_array($this->maniaControl->pluginManager->getActivePlugins())) {
			foreach($this->maniaControl->pluginManager->getActivePlugins() as $plugin) {
				if(!is_null($plugin::getId()) && is_numeric($plugin::getId())) {
					$activePlugins[] = $plugin::getId();
				}
			}
		}

		$properties['ActivePlugins'] = $activePlugins;

		$json = json_encode($properties);
		$info = base64_encode($json);

		$this->maniaControl->fileReader->loadFile(ManiaControl::URL_WEBSERVICE . "/usagereport?info=" . urlencode($info), function ($response, $error) {
			$response = json_decode($response);
			if ($error || !$response) {
				$this->maniaControl->log("Error while Sending data: " . $error);
			}
		});
	}
} 