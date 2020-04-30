<?php

namespace ManiaControl\Callbacks\Structures\Common\Models;

use ManiaControl\General\JsonSerializable;
use ManiaControl\General\JsonSerializeTrait;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * TeamScore Model
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CommonTeamScore implements UsageInformationAble, JsonSerializable {
	use UsageInformationTrait, JsonSerializeTrait;

	private $teamId;
	private $name;
	private $roundPoints;
	private $mapPoints;
	private $matchPoints;

	/**
	 * Get the Team Id
	 *
	 * @api
	 * @return mixed
	 */
	public function getTeamId() {
		return $this->teamId;
	}

	/**
	 * Sets the TeamId
	 *
	 * @api
	 * @param mixed $id
	 */
	public function setTeamId($id) {
		$this->teamId = $id;
	}

	/**
	 * Gets the Name
	 *
	 * @api
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the Name
	 *
	 * @api
	 * @param mixed $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Gets the Round Points
	 *
	 * @api
	 * @return mixed
	 */
	public function getRoundPoints() {
		return $this->roundPoints;
	}

	/**
	 * Sets the Round Points
	 *
	 * @api
	 * @param mixed $roundPoints
	 */
	public function setRoundPoints($roundPoints) {
		$this->roundPoints = $roundPoints;
	}

	/**
	 * Gets the Map Points
	 *
	 * @api
	 * @return mixed
	 */
	public function getMapPoints() {
		return $this->mapPoints;
	}

	/**
	 * Sets the Mappoints
	 *
	 * @api
	 * @param mixed $mapPoints
	 */
	public function setMapPoints($mapPoints) {
		$this->mapPoints = $mapPoints;
	}

	/**
	 * Gets the Matchpoints
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