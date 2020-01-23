<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;


use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the Stunt Event Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnStuntEventStructure extends BasePlayerTimeStructure {
	private $raceTime;
	private $lapTime;
	private $stuntsScore;
	private $figureName;
	private $angle;
	private $points;
	private $combo;
	private $isStraight;
	private $isReverse;
	private $isMasterJump;
	private $factor;

	/**
	 * OnWayPointEventStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj = $this->getPlainJsonObject();

		$this->raceTime         = (int) $jsonObj->racetime;
		$this->lapTime          = (int) $jsonObj->laptime;
		$this->stuntsScore      = $jsonObj->stuntsscore;
		$this->figureName       = $jsonObj->figure;
		$this->angle            = $jsonObj->angle;
		$this->points           = $jsonObj->points;
		$this->combo            = $jsonObj->combo;
		$this->isStraight       = $jsonObj->isstraight;
		$this->isReverse        = $jsonObj->isreverse;
		$this->isMasterJump     = $jsonObj->ismasterjump;
		$this->factor           = $jsonObj->factor;
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
	 * @return mixed
	 */
	public function getFigureName() {
		return $this->figureName;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getAngle() {
		return $this->angle;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getPoints() {
		return $this->points;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getCombo() {
		return $this->combo;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getIsStraight() {
		return $this->isStraight;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getIsReverse() {
		return $this->isReverse;
	}

	/**
	 * @api
	 * @return mixed
	 */
	public function getIsMasterJump() {
		return $this->isMasterJump;
	}

	/**
	 * @api
	 * @return mixed Points multiplier
	 */
	public function getFactor() {
		return $this->factor;
	}

}