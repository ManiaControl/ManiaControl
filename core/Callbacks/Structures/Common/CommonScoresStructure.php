<?php

namespace ManiaControl\Callbacks\Structures\Common;


use ManiaControl\Callbacks\Structures\Common\Models\CommonPlayerScore;
use ManiaControl\Callbacks\Structures\ShootMania\Models\TeamScore;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnScores Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CommonScoresStructure extends BaseResponseStructure {
	protected $section;
	protected $useTeams;
	protected $winnerTeam;
	protected $winnerPlayer;
	protected $teamScores   = array();
	protected $playerScores = array();

	/**
	 * CommonScoresStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj = $this->getPlainJsonObject();

		$this->section    = $jsonObj->section;
		$this->useTeams   = $jsonObj->useteams;
		$this->winnerTeam = $jsonObj->winnerteam;

		$this->winnerPlayer = $this->maniaControl->getPlayerManager()->getPlayer($jsonObj->winnerplayer);

		foreach ($jsonObj->teams as $team) {
			if ($this instanceof \ManiaControl\Callbacks\Structures\TrackMania\OnScoresStructure) {
				$teamScore = new \ManiaControl\Callbacks\Structures\TrackMania\Models\TeamScore();
			} else {
				$teamScore = new \ManiaControl\Callbacks\Structures\ShootMania\Models\TeamScore();
			}

			$teamScore->setTeamId($team->id);
			$teamScore->setName($team->name);
			$teamScore->setRoundPoints($team->roundpoints);
			$teamScore->setMatchPoints($team->matchpoints);
			$teamScore->setMapPoints($team->mappoints);

			$this->teamScores[$team->id] = $teamScore; //TODO verify that different teams have different ids
		}
	}

	/**
	 * Get the Winner Player Object
	 *
	 * @api
	 * @return \ManiaControl\Players\Player
	 */
	public function getWinnerPlayer() {
		return $this->winnerPlayer;
	}

	/**
	 *  < Current progress of the match. Can be "" | "EndRound" | "EndMap" | "EndMatch"
	 *
	 * @api
	 * @return string
	 */
	public function getSection() {
		return $this->section;
	}

	/**
	 * Returns if the GameMode uses Teams or not
	 *
	 * @api
	 * @return boolean
	 */
	public function getUseTeams() {
		return $this->useTeams;
	}

	/**
	 * Get the Winner Team Id
	 *
	 * @api
	 * @return int
	 */
	public function getWinnerTeamId() {
		return $this->winnerTeam;
	}

	/**
	 * Returns the TeamScores
	 *
	 * @api
	 * @return TeamScore[]
	 */
	public function getTeamScores() {
		return $this->teamScores;
	}

	/**
	 * Get the Player Scores
	 *
	 * @api
	 * @return CommonPlayerScore[]
	 */
	public function getPlayerScores() {
		return $this->playerScores;
	}
}