<?php

namespace ManiaControl\Callbacks\Structures\Common\Models;

use ManiaControl\General\JsonSerializable;
use ManiaControl\General\JsonSerializeTrait;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * TeamScore Model
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
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
	 * @return mixed
	 */
	public function getTeamId() {
		return $this->teamId;
	}

	/**
	 * @param mixed $id
	 */
	public function setTeamId($id) {
		$this->teamId = $id;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getRoundPoints() {
		return $this->roundPoints;
	}

	/**
	 * @param mixed $roundPoints
	 */
	public function setRoundPoints($roundPoints) {
		$this->roundPoints = $roundPoints;
	}

	/**
	 * @return mixed
	 */
	public function getMapPoints() {
		return $this->mapPoints;
	}

	/**
	 * @param mixed $mapPoints
	 */
	public function setMapPoints($mapPoints) {
		$this->mapPoints = $mapPoints;
	}

	/**
	 * @return mixed
	 */
	public function getMatchPoints() {
		return $this->matchPoints;
	}

	/**
	 * @param mixed $matchPoints
	 */
	public function setMatchPoints($matchPoints) {
		$this->matchPoints = $matchPoints;
	}
}