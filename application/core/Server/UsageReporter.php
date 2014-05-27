<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;
use ManiaControl\Utils\Formatter;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Class reporting ManiaControl Usage for the Server
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class UsageReporter implements TimerListener {
	/*
	 * Constants
	 */
	const UPDATE_MINUTE_COUNT  = 10;
	const SETTING_REPORT_USAGE = 'Report Usage to $lManiaControl.com$l';

	/*
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

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_REPORT_USAGE, true);

		$this->maniaControl->timerManager->registerTimerListening($this, 'reportUsage', 1000 * 60 * self::UPDATE_MINUTE_COUNT);
	}

	/**
	 * Report Usage every xx Minutes
	 *
	 * @param float $time
	 */
	public function reportUsage($time) {
		if (DEV_MODE || !$this->maniaControl->settingManager->getSettingValue($this, self::SETTING_REPORT_USAGE)) {
			return;
		}

		$properties                        = array();
		$properties['ManiaControlVersion'] = ManiaControl::VERSION;
		$properties['OperatingSystem']     = php_uname();
		$properties['PHPVersion']          = phpversion();
		$properties['ServerLogin']         = $this->maniaControl->server->login;
		$properties['TitleId']             = $this->maniaControl->server->titleId;
		$properties['ServerName']          = Formatter::stripDirtyCodes($this->maniaControl->client->getServerName());
		$properties['UpdateChannel']       = $this->maniaControl->updateManager->getCurrentUpdateChannelSetting();

		$properties['PlayerCount']     = $this->maniaControl->playerManager->getPlayerCount();
		$properties['MemoryUsage']     = memory_get_usage();
		$properties['MemoryPeakUsage'] = memory_get_peak_usage();

		$maxPlayers               = $this->maniaControl->client->getMaxPlayers();
		$properties['MaxPlayers'] = $maxPlayers['CurrentValue'];

		try {
			$scriptName               = $this->maniaControl->client->getScriptName();
			$properties['ScriptName'] = $scriptName['CurrentValue'];
		} catch (GameModeException $e) {
			$properties['ScriptName'] = '';
		}

		$properties['ActivePlugins'] = $this->maniaControl->pluginManager->getActivePluginsIds();

		$json = json_encode($properties);
		$info = base64_encode($json);

		$url  = ManiaControl::URL_WEBSERVICE . '/usagereport?info=' . urlencode($info);
		$self = $this;
		$this->maniaControl->fileReader->loadFile($url, function ($response, $error) use (&$self) {
			$response = json_decode($response);
			if ($error || !$response) {
				$self->maniaControl->log('Error while Sending data: ' . print_r($error, true));
			}
		});
	}
}
