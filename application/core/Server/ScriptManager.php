<?php

namespace ManiaControl\Server;

use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;
use Maniaplanet\DedicatedServer\Xmlrpc\NotInScriptModeException;

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
		try {
			$scriptSettings = $this->maniaControl->client->getModeScriptSettings();
		} catch (NotInScriptModeException $e) {
			return false;
		}

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
			trigger_error("Couldn't set Mode Script Settings to {$actionName}able Script Sallbacks. " . $e->getMessage());
			return false;
		}
		$this->maniaControl->log("Script Callbacks successfully {$actionName}abled!");
		return true;
	}
}
