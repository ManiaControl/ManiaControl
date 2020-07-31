<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;


use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Utils\Formatter;

/**
 * Structure Class for the Default Event Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnWayPointEventStructure extends BasePlayerTimeStructure {
	private $raceTime;
	private $lapTime;
	private $stuntsScore;
	private $checkPointInRace;
	private $checkPointInLap;
	private $isEndRace;
	private $isEndLap;
	private $blockId;
	private $speed;
	private $distance;
	private $lapNumber;

	/**
	 * OnWayPointEventStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->raceTime         = (int) $this->getPlainJsonObject()->racetime;
		$this->lapTime          = (int) $this->getPlainJsonObject()->laptime;
		$this->stuntsScore      = isset($this->getPlainJsonObject()->stuntsscore) ? $this->getPlainJsonObject()->stuntsscore : null;
		$this->checkPointInRace = (int) $this->getPlainJsonObject()->checkpointinrace;
		$this->checkPointInLap  = (int) $this->getPlainJsonObject()->checkpointinlap;
		$this->isEndRace        = Formatter::parseBoolean($this->getPlainJsonObject()->isendrace);
		$this->isEndLap         = Formatter::parseBoolean($this->getPlainJsonObject()->isendlap);
		$this->blockId          = $this->getPlainJsonObject()->blockid;
		$this->speed            = $this->getPlainJsonObject()->speed;
		$this->distance         = isset($this->getPlainJsonObject()->distance) ? $this->getPlainJsonObject()->distance : null;

		if ($this->checkPointInRace > 0) {
			$currentMap      = $this->maniaControl->getMapManager()->getCurrentMap();
			$this->lapNumber = intval($this->checkPointInRace / $currentMap->nbCheckpoints);
		}
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
	public function getIsEndRace() {
		return $this->isEndRace;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getIsEndLap() {
		return $this->isEndLap;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getBlockId() {
		return $this->blockId;
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
	 * Gets the Current Lap Number
	 *
	 * @return float|int
	 */
	public function getLapNumber() {
		return $this->lapNumber;
	}

}