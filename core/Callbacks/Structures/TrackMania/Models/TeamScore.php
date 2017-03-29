<?php

namespace ManiaControl\Callbacks\Structures\TrackMania\Models;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * TeamScore Model
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class TeamScore implements UsageInformationAble {
	use UsageInformationTrait;

	private $id;
	private $name;
	private $roundPoints;
	private $mapPoints;
	private $matchPoints;

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id) {
		$this->id = $id;
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