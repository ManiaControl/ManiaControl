<?php

namespace ManiaControl\Script;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Manager for Game Mode Script related Stuff
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptManager implements UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $isScriptMode = null;

	/**
	 * Construct a new script manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Enable script callbacks
	 *
	 * @param bool $enable
	 * @return bool
	 */
	public function enableScriptCallbacks() {
		if (!$this->isScriptMode()) {
			return false;
		}

		try {
			$scriptSettings = $this->maniaControl->getClient()->getModeScriptSettings();
		} catch (GameModeException $e) {
			var_dump("test");
			return false;
		}

		//TODO remove later, than only the last 2 lines are needed in future
		if (array_key_exists('S_UseScriptCallbacks', $scriptSettings)) {
			$scriptSettings['S_UseScriptCallbacks'] = true;
			$this->maniaControl->getClient()->setModeScriptSettings($scriptSettings);
		}

		$this->maniaControl->getModeScriptEventManager()->enableCallbacks();
		Logger::logInfo("Script Callbacks successfully enabled!");
		return true;
	}

	/**
	 * Check whether the Server is running in Script Mode
	 *
	 * @return bool
	 */
	public function isScriptMode() {
		if (is_null($this->isScriptMode)) {
			$gameMode           = $this->maniaControl->getClient()->getGameMode();
			$this->isScriptMode = ($gameMode === 0);
		}
		return $this->isScriptMode;
	}
}
