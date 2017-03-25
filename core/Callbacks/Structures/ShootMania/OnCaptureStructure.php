<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\Callbacks\Structures\ShootMania\Models\Landmark;
use ManiaControl\Callbacks\Structures\ShootMania\Models\Position;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Structure Class for the OnCapture Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnCaptureStructure extends BaseStructure {

	public  $time;
	private $landMark;

	private $playerArray = array();

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj = $this->getPlainJsonObject();

		$this->time        = $jsonObj->time;
		$this->playerArray = $jsonObj->players;

		$this->landMark = new Landmark();
		$this->landMark->setTag($jsonObj->landmark->tag);
		$this->landMark->setOrder($jsonObj->landmark->tag);
		$this->landMark->setId($jsonObj->landmark->tag);

		$position = new Position();
		$position->setX($jsonObj->landmark->position->x);
		$position->setY($jsonObj->landmark->position->y);
		$position->setZ($jsonObj->landmark->position->z);

		$this->landMark->setPosition($position);

		$this->shooter = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->shooter);
	}

	/** Dumps the Object with some Information */
	public function dump() {
		var_dump($this->landMark);
		parent::dump();
	}

	/**
	 * Get the logins
	 *
	 * @return array
	 */
	public function getLoginArray() {
		return $this->playerArray;
	}

	/**
	 * Get the players
	 *
	 * @return Player[]
	 */
	public function getPlayerArray() {
		$playerArray = array();
		foreach ($this->playerArray as $login) {
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
			if ($player) {
				$playerArray[$login] = $player;
			}
		}
		return $playerArray;
	}

	/**
	 * @return LandMark
	 */
	public function getLandMark() {
		return $this->landMark;
	}

	/**
	 * @param mixed $landMark
	 */
	public function setLandMark(Landmark $landMark) {
		$this->landMark = $landMark;
	}
}