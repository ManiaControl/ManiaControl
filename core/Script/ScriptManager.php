<?php

namespace ManiaControl\Script;

use ManiaControl\Callbacks\Structures\Common\StatusCallbackStructure;
use ManiaControl\Callbacks\Structures\ManiaPlanet\ModeUseTeamsStructure;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Manager for Game Mode Script related Stuff
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptManager implements UsageInformationAble {
	use UsageInformationTrait;

	//TODO add CB to MC Website
	const CB_PAUSE_STATUS_CHANGED = "ManiaControl.ScriptManager.PauseStatusChanged";

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl  = null;
	private $isScriptMode  = null;
	private $modeUsesPause = false;

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

		//Checks if the Server is currently in TeamMode and sets it
		$this->maniaControl->getModeScriptEventManager()->isTeamMode()->setCallable(function (ModeUseTeamsStructure $structure) {
			if ($structure->modeIsUsingTeams()) {
				$this->maniaControl->getServer()->setTeamMode(true);
			}
		});

		//Checks if the Mode Uses Pause
		$this->checkIfTheModeUsesPause();

		return true;
	}

	/**
	 * Checks if the Mode Uses Pause Async
	 */
	private function checkIfTheModeUsesPause() {
		try {
			$scriptInfos = $this->maniaControl->getClient()->getModeScriptInfo();

			foreach ($scriptInfos->commandDescs as $param) { //TODO Mp3, can be removed later
				if ($param->name === "Command_ForceWarmUp" || $param->name === "Command_SetPause") {
					$this->setPauseStatus(true);
					return;
				}
			}
		} catch (GameModeException $e) {
		}

		//Checks if the Script has implemented the Pause Feature and Sets the Information
		$this->maniaControl->getModeScriptEventManager()->getPauseStatus()->setCallable(function (StatusCallbackStructure $structure) {
			if ($structure->isAvailable()) {
				$this->setPauseStatus(true);
			}
		});
	}

	/**
	 * Sets the New Pause Status
	 *
	 * @param boolean $status
	 */
	private function setPauseStatus($status) {
		$status = (boolean) $status;
		if ($this->modeUsesPause != $status) {
			$this->modeUsesPause = $status;
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_PAUSE_STATUS_CHANGED, $status);
		}
	}

	/**
	 * Check whether the Server is running in Script Mode
	 *
	 * @api
	 * @return bool
	 */
	public function isScriptMode() {
		if (is_null($this->isScriptMode)) {
			$gameMode           = $this->maniaControl->getClient()->getGameMode();
			$this->isScriptMode = ($gameMode === 0);
		}
		return $this->isScriptMode;
	}

	/**
	 * Checks if the Mode Can be Forced to a Pause
	 *
	 * @api
	 * @return bool
	 */
	public function modeUsesPause() {
		return $this->modeUsesPause;
	}

	/**
	 * Checks if the Mode is in TeamMode
	 *
	 * @api
	 * @return bool
	 */
	public function modeIsTeamMode() {
		return $this->maniaControl->getServer()->isTeamMode();
	}
}
