<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;


use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Structure Class for the OnCapture Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnCaptureStructure extends BaseStructure {

	private $time;
	private $landMark;

	private $playerArray = array();

	/**
	 * OnCaptureStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj = $this->getPlainJsonObject();

		$this->time        = $jsonObj->time;
		$this->playerArray = $jsonObj->players;

		//TODO Verify why it doesnt work for siege
		/*if (property_exists($jsonObj, 'landmark')) {
			$this->landMark = new Landmark();
			$this->landMark->setTag($jsonObj->landmark->tag);
			$this->landMark->setOrder($jsonObj->landmark->order);
			$this->landMark->setId($jsonObj->landmark->id);

			$position = new Position();
			$position->setX($jsonObj->landmark->position->x);
			$position->setY($jsonObj->landmark->position->y);
			$position->setZ($jsonObj->landmark->position->z);

			$this->landMark->setPosition($position);
		}*/
	}

	/**
	 * Get the logins as Array
	 *
	 * @api
	 * @return string[]
	 */
	public function getLoginArray() {
		return $this->playerArray;
	}

	/**
	 * Get the Players as Player Array
	 *
	 * @api
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
	 * Returns Information about the Captured Landmark
	 *
	 * @api
	 * @return \ManiaControl\Callbacks\Structures\ShootMania\Models\LandMark
	 */
	public function getLandMark() {
		return $this->landMark;
	}
}