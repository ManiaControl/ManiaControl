<?php

namespace ManiaControl\Server;

use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

/**
 * Manager for Game Mode Script related Stuff
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptManager {
	/*
	 * Private Properties
	 */
	public $maniaControl = null;

	/*
	 * Private Properties
	 */
	private $isScriptMode = null;

	/**
	 * Construct a new Script Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Enable Script Callbacks
	 *
	 * @param bool $enable
	 * @return bool
	 */
	public function enableScriptCallbacks($enable = true) {
		if (!$this->isScriptMode()) {
			return false;
		}
		$scriptSettings = $this->maniaControl->client->getModeScriptSettings();

		if (!array_key_exists('S_UseScriptCallbacks', $scriptSettings)) {
			return false;
		}

		$scriptSettings['S_UseScriptCallbacks'] = (bool)$enable;
		$actionName                             = ($enable ? 'en' : 'dis');

		try {
			$this->maniaControl->client->setModeScriptSettings($scriptSettings);
		} catch (Exception $e) {
			// TODO temp added 19.04.2014
			$this->maniaControl->errorHandler->handleException($e, false);
			trigger_error("Couldn't set Mode Script Settings to {$actionName}able Script Callbacks. " . $e->getMessage());
			return false;
		}
		$this->maniaControl->log("Script Callbacks successfully {$actionName}abled!");
		return true;
	}

	/**
	 * Check if the Server is running in Script Mode
	 *
	 * @return bool
	 */
	public function isScriptMode() {
		if ($this->isScriptMode === null) {
			$gameMode           = $this->maniaControl->client->getGameMode();
			$this->isScriptMode = ($gameMode === 0);
		}
		return $this->isScriptMode;
	}
}
