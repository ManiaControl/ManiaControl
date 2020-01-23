<?php

namespace ManiaControl\Callbacks\Structures\TrackMania\Models;

use ManiaControl\Callbacks\Structures\Common\Models\CommonPlayerScore;

//TODO proper return descriptions on getter methods -> use autogenerate for setter/getter + docs

/**
 * PlayerScore Model
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerScore extends CommonPlayerScore {

	private $bestRaceTime;
	private $bestLapTime;
	private $stuntScore;
	private $bestRaceRespawns;
	private $bestRaceCheckpoints;
	private $bestLapRespawns;
	private $bestLapCheckpoints;
	private $prevRaceTime;
	private $prevRaceRespawns;
	private $prevRaceCheckpoints;
	private $prevStuntsScore;

	/**
	 * Returns the Rank
	 *
	 * @api
	 * @return int
	 */
	public function getRank() {
		return $this->rank;
	}

	/**
	 * Sets the Rank
	 *
	 * @api
	 * @param int $rank
	 */
	public function setRank($rank) {
		$this->rank = $rank;
	}


	/**
	 *   Gets the bestRaceTime
	 *
	 * @api
	 * @param int $bestraceTime
	 */
	public function getBestraceTime() {
		return $this->bestRaceTime;
	}

	/**
	 *   Gets the bestlapTime
	 *
	 * @api
	 * @param int $bestlapTime
	 */
	public function getBestlapTime() {
		return $this->bestLapTime;
	}

	/**
	 *   Gets the StuntScore
	 *
	 * @api
	 * @param int $bestraceTime
	 */
	public function getStuntScore() {
		return $this->stuntScore;
	}

	/**
	 * @api
	 * @param mixed $bestRaceTime
	 */
	public function setBestRaceTime($bestRaceTime) {
		$this->bestRaceTime = $bestRaceTime;
	}

	/**
	 * @api
	 * @param mixed $bestLapTime
	 */
	public function setBestLapTime($bestLapTime) {
		$this->bestLapTime = $bestLapTime;
	}

	/**
	 * @api
	 * @param mixed $stuntScore
	 */
	public function setStuntScore($stuntScore) {
		$this->stuntScore = $stuntScore;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getBestRaceRespawns() {
		return $this->bestRaceRespawns;
	}

	/**
	 * @api
	 * @param mixed $bestRaceRespawns
	 */
	public function setBestRaceRespawns($bestRaceRespawns) {
		$this->bestRaceRespawns = $bestRaceRespawns;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getBestRaceCheckpoints() {
		return $this->bestRaceCheckpoints;
	}

	/**
	 * @api
	 * @param mixed $bestRaceCheckpoints
	 */
	public function setBestRaceCheckpoints($bestRaceCheckpoints) {
		$this->bestRaceCheckpoints = $bestRaceCheckpoints;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getBestLapRespawns() {
		return $this->bestLapRespawns;
	}

	/**
	 * @api
	 * @param mixed $bestLapRespawns
	 */
	public function setBestLapRespawns($bestLapRespawns) {
		$this->bestLapRespawns = $bestLapRespawns;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getBestLapCheckpoints() {
		return $this->bestLapCheckpoints;
	}

	/**
	 * @api
	 * @param mixed $bestLapCheckpoints
	 */
	public function setBestLapCheckpoints($bestLapCheckpoints) {
		$this->bestLapCheckpoints = $bestLapCheckpoints;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getPrevRaceTime() {
		return $this->prevRaceTime;
	}

	/**
	 * @api
	 * @param mixed $prevRaceTime
	 */
	public function setPrevRaceTime($prevRaceTime) {
		$this->prevRaceTime = $prevRaceTime;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getPrevRaceRespawns() {
		return $this->prevRaceRespawns;
	}

	/**
	 * @api
	 * @param mixed $prevRaceRespawns
	 */
	public function setPrevRaceRespawns($prevRaceRespawns) {
		$this->prevRaceRespawns = $prevRaceRespawns;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getPrevRaceCheckpoints() {
		return $this->prevRaceCheckpoints;
	}

	/**
	 * @api
	 * @param mixed $prevRaceCheckpoints
	 */
	public function setPrevRaceCheckpoints($prevRaceCheckpoints) {
		$this->prevRaceCheckpoints = $prevRaceCheckpoints;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getPrevStuntsScore() {
		return $this->prevStuntsScore;
	}

	/**
	 * @api
	 * @param mixed $prevStuntsScore
	 */
	public function setPrevStuntsScore($prevStuntsScore) {
		$this->prevStuntsScore = $prevStuntsScore;
	}


}