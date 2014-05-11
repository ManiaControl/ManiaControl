<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\ManiaControl;

/**
 * Class managing the current Match Settings of the Server
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MatchSettingsManager implements CallbackListener {
	/*
	 * Constants
	 */
	const CB_TEAM_MODE_CHANGED = 'MatchSettings.TeamModeChanged';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $teamMode = null;

	/**
	 * Construct a new Match Settings Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Set whether the Server Runs a Team-Based Mode or not
	 *
	 * @param bool $teamMode
	 */
	public function setTeamMode($teamMode = true) {
		$oldStatus      = $this->teamMode;
		$this->teamMode = (bool)$teamMode;

		// Trigger callback
		if ($oldStatus !== $this->teamMode | $oldStatus === null) {
			$this->maniaControl->callbackManager->triggerCallback(self::CB_TEAM_MODE_CHANGED, $teamMode);
		}
	}

	/**
	 * Check if the Server Runs a Team-Based Mode
	 *
	 * @return bool
	 */
	public function isTeamMode() {
		return $this->teamMode;
	}


	/**
	 * Fetch the current Game Mode
	 *
	 * @param bool $stringValue
	 * @param int  $parseValue
	 * @return int | string
	 */
	public function getGameMode($stringValue = false, $parseValue = null) {
		if (is_int($parseValue)) {
			$gameMode = $parseValue;
		} else {
			$gameMode = $this->maniaControl->client->getGameMode();
		}
		if ($stringValue) {
			switch ($gameMode) {
				case 0:
					return 'Script';
				case 1:
					return 'Rounds';
				case 2:
					return 'TimeAttack';
				case 3:
					return 'Team';
				case 4:
					return 'Laps';
				case 5:
					return 'Cup';
				case 6:
					return 'Stunts';
				default:
					return 'Unknown';
			}
		}
		return $gameMode;
	}
}
