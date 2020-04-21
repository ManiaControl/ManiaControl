<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;


use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\ManiaControl;


/**
 * Structure Class for the On Respawn Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnRespawnStructure extends BasePlayerTimeStructure {
	private $numberOfRespawns;
	private $raceTime;
	private $lapTime;
	private $stuntsScore;
	private $checkPointInRace;
	private $checkPointInLap;
	private $speed;
	private $distance;

	/**
	 * OnWayPointEventStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj = $this->getPlainJsonObject();

		$this->numberOfRespawns = (int) $jsonObj->nbrespawns;
		$this->raceTime         = (int) $jsonObj->racetime;
		$this->lapTime          = (int) $jsonObj->laptime;
		$this->stuntsScore      = $jsonObj->stuntsscore;
		$this->checkPointInRace = (int) $jsonObj->checkpointinrace;
		$this->checkPointInLap  = (int) $jsonObj->checkpointinlap;
		$this->speed            = $jsonObj->speed;
		$this->distance         = $jsonObj->distance;
	}

	/**
	 * @api
	 * @return int
	 */
	public function getRaceTime() {
		return $this->raceTime;
	}

	/**
	 * @api
	 * @return int
	 */
	public function getLapTime() {
		return $this->lapTime;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getStuntsScore() {
		return $this->stuntsScore;
	}

	/**
	 * @api
	 * @return int
	 */
	public function getCheckPointInRace() {
		return $this->checkPointInRace;
	}

	/**
	 * @api
	 * @return int
	 */
	public function getCheckPointInLap() {
		return $this->checkPointInLap;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getSpeed() {
		return $this->speed;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getDistance() {
		return $this->distance;
	}

	/**
	 * @api
	 * @return int
	 */
	public function getNumberOfRespawns() {
		return $this->numberOfRespawns;
	}

}