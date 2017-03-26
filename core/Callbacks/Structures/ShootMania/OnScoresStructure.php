<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\Callbacks\Structures\ShootMania\Models\TeamScore;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnScores Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnScoresStructure extends BaseStructure {
	private $responseId;
	private $section;
	private $useTeams;
	private $winnerTeam;
	private $winnerPlayer;
	private $teamScores = array();
	private $players; //TODO implement

	//TODO test
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj = $this->getPlainJsonObject();

		$this->responseId = $jsonObj->responseId;
		$this->section    = $jsonObj->section;
		$this->useTeams   = $jsonObj->useTeams;
		$this->winnerTeam = $jsonObj->winnerTeam;

		$this->winnerPlayer = $this->maniaControl->getPlayerManager()->getPlayer($jsonObj->winnerplayer);

		foreach ($jsonObj->teams as $team) {
			$teamScore = new TeamScore();
			$teamScore->setId($team->id);
			$teamScore->setName($team->name);
			$teamScore->setRoundPoints($team->roundpoints);
			$teamScore->setMatchPoints($team->matchpoints);
			$teamScore->setMapPoints($team->mappoints);

			$this->teamScores[$team->id] = $teamScore; //TODO verify that different teams have different ids
		}

		//TODO implement player
	}

	/**
	 * Get the Winner Player Object
	 *
	 * @return \ManiaControl\Players\Player
	 */
	public function getWinnerPlayer() {
		return $this->winnerPlayer;
	}

	/**
	 * Get the Response Id
	 *
	 * @return string
	 */
	public function getResponseId() {
		return $this->responseId;
	}

	/**
	 *  < Current progress of the match. Can be "" | "EndRound" | "EndMap" | "EndMatch"
	 *
	 * @return string
	 */
	public function getSection() {
		return $this->section;
	}

	/**
	 * Returns if the GameMode uses Teams or not
	 *
	 * @return boolean
	 */
	public function getUseTeams() {
		return $this->useTeams;
	}

	/**
	 * Get the Winner Team Id
	 *
	 * @return int
	 */
	public function getWinnerTeamId() {
		return $this->winnerTeam;
	}

	/**
	 * Returns the TeamScores
	 *
	 * @return TeamScore[]
	 */
	public function getTeamScores() {
		return $this->teamScores;
	}

	/**
	 * Get The Player Scores
	 *
	 * @return mixed
	 */
	public function getPlayers() {
		//TODO proper implementation
		return $this->players;
	}
}