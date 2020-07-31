<?php

namespace ManiaControl\Callbacks\Structures\Common\Models;


use ManiaControl\General\JsonSerializable;
use ManiaControl\General\JsonSerializeTrait;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Players\Player;

/**
 * Common PlayerStructure Model
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CommonPlayerScore implements UsageInformationAble, JsonSerializable {
	use UsageInformationTrait, JsonSerializeTrait;

	protected $player;
	protected $rank;
	protected $roundPoints;
	protected $mapPoints;
	protected $matchPoints;

	/**
	 * Returns the Player
	 *
	 * @api
	 * @return \ManiaControl\Players\Player
	 */
	public function getPlayer() {
		return $this->player;
	}

	/**
	 * Returns the Player
	 *
	 * @api
	 * @param \ManiaControl\Players\Player $player
	 */
	public function setPlayer(Player $player) {
		$this->player = $player;
	}

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
	 * Gets the Round Points
	 *
	 * @api
	 * @return int
	 */
	public function getRoundPoints() {
		return $this->roundPoints;
	}

	/**
	 * Sets the RoundPoints
	 *
	 * @api
	 * @param int $roundPoints
	 */
	public function setRoundPoints($roundPoints) {
		$this->roundPoints = $roundPoints;
	}

	/**
	 * Gets the Map Points
	 *
	 * @api
	 * @return int
	 */
	public function getMapPoints() {
		return $this->mapPoints;
	}

	/**
	 * Sets the Map Points
	 *
	 * @api
	 * @param int $mapPoints
	 */
	public function setMapPoints($mapPoints) {
		$this->mapPoints = $mapPoints;
	}

	/**
	 * Gets the Match Points
	 *
	 * @api
	 * @return mixed
	 */
	public function getMatchPoints() {
		return $this->matchPoints;
	}

	/**
	 * Sets the Match Points
	 *
	 * @api
	 * @param mixed $matchPoints
	 */
	public function setMatchPoints($matchPoints) {
		$this->matchPoints = $matchPoints;
	}

}