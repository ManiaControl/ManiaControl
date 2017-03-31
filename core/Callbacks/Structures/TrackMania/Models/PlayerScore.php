<?php

namespace ManiaControl\Callbacks\Structures\TrackMania\Models;

use ManiaControl\Callbacks\Structures\Common\Models\CommonPlayerScore;

use ManiaControl\Players\Player;
//TODO proper return descriptions on getter methods -> use autogenerate for setter/getter + docs
/**
 * PlayerScore Model
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerScore extends CommonPlayerScore {

	private $bestRaceTime;
	private $bestLapTime;
	private $stuntScore;


	/**
	 * Returns the Rank
	 *
	 * @return int
	 */
	public function getRank() {
		return $this->rank;
	}

	/**
	 * Sets the Rank
	 *
	 * @param int $rank
	 */
	public function setRank($rank) {
		$this->rank = $rank;
	}



	/**
	*   Gets the bestRaceTime
	*
	* @param int $bestraceTime
	*/
	public function getBestraceTime(){
		return $this->bestRaceTime;
	}

	/**
	*   Gets the bestlapTime
	*
	* @param int $bestlapTime
	*/
	public function getBestlapTime(){
		return $this->bestLapTime;
	}
	
	/**
	*   Gets the StuntScore
	*
	* @param int $bestraceTime
	*/
	public function getStuntScore(){
		return $this->stuntScore;
	}

	/**
	 * @param mixed $bestRaceTime
	 */
	public function setBestRaceTime($bestRaceTime) {
		$this->bestRaceTime = $bestRaceTime;
	}

	/**
	 * @param mixed $bestLapTime
	 */
	public function setBestLapTime($bestLapTime) {
		$this->bestLapTime = $bestLapTime;
	}

	/**
	 * @param mixed $stuntScore
	 */
	public function setStuntScore($stuntScore) {
		$this->stuntScore = $stuntScore;
	}

}