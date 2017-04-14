<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;


use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Utils\Formatter;

/**
 * Structure Class for the Default Event Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
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
		$this->stuntsScore      = $this->getPlainJsonObject()->stuntsscore;
		$this->checkPointInRace = (int) $this->getPlainJsonObject()->checkpointinrace;
		$this->checkPointInLap  = (int) $this->getPlainJsonObject()->checkpointinlap;
		$this->isEndRace        = $this->getPlainJsonObject()->isendrace;
		$this->isEndLap         = $this->getPlainJsonObject()->isendlap;
		$this->blockId          = $this->getPlainJsonObject()->blockid;
		$this->speed            = $this->getPlainJsonObject()->speed;
		$this->distance         = $this->getPlainJsonObject()->distance;

		// Build callback //TODO remove the old lagacy stuff and update the uses to the new Structure
		$wayPointCallback              = new RecordCallback();
		$wayPointCallback->rawCallback = $data;
		$wayPointCallback->setPlayer($this->getPlayer());
		$wayPointCallback->blockId       = $this->blockId;
		$wayPointCallback->time          = $this->raceTime;
		$wayPointCallback->checkpoint    = $this->checkPointInRace;
		$wayPointCallback->isEndRace     = Formatter::parseBoolean($this->isEndRace);
		$wayPointCallback->lapTime       = $this->lapTime;
		$wayPointCallback->lapCheckpoint = $this->checkPointInLap;
		$wayPointCallback->lap           = 0;
		$wayPointCallback->isEndLap      = Formatter::parseBoolean($this->isEndLap);
		if ($wayPointCallback->checkpoint > 0) {
			$currentMap            = $this->maniaControl->getMapManager()->getCurrentMap();
			$wayPointCallback->lap += $wayPointCallback->checkpoint / $currentMap->nbCheckpoints;
		}
		if ($wayPointCallback->isEndRace) {
			$wayPointCallback->name = $wayPointCallback::FINISH;
		} else if ($wayPointCallback->isEndLap) {
			$wayPointCallback->name = $wayPointCallback::LAPFINISH;
		} else {
			$wayPointCallback->name = $wayPointCallback::CHECKPOINT;
		}
		$this->maniaControl->getCallbackManager()->triggerCallback($wayPointCallback);
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

}