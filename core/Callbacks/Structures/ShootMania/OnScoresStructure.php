<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\Callbacks\Structures\ShootMania\Models\TeamScore;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Structure Class for the OnScores Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnScoresStructure extends BaseStructure {
	public  $responseId;
	public  $section;
	public  $useTeams;
	public  $winnerTeam;
	public  $winnerPlayer;
	private $teamScores = array();
	public  $players; //TODO implement

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

	/** Dumps the Object with some Information */
	public function dump() {
		parent::dump();
		var_dump("With getWinnerPlayer() you get a Player Object");
		var_dump($this->teamScores);
	}


	/**
	 * @return Player
	 */
	public function getWinnerPlayer() {
		return $this->winnerPlayer;
	}

	/**
	 * @return mixed
	 */
	public function getResponseId() {
		return $this->responseId;
	}

	/**
	 * @return mixed
	 */
	public function getSection() {
		return $this->section;
	}

	/**
	 * @return boolean
	 */
	public function getUseTeams() {
		return $this->useTeams;
	}

	/**
	 * @return int
	 */
	public function getWinnerTeam() {
		return $this->winnerTeam;
	}

	/**
	 * @return TeamScore[]
	 */
	public function getTeamScores() {
		return $this->teamScores;
	}

	/**
	 * @return mixed
	 */
	public function getPlayers() {
		//TODO proper implementation
		return $this->players;
	}
}