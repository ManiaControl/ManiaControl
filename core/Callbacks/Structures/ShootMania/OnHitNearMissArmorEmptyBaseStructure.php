<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\Callbacks\Structures\ShootMania\Models\Position;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;


/**
 * Structure Base Class for the OnHit/OnNearMiss/OnArmorEmpty Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnHitNearMissArmorEmptyBaseStructure extends BaseStructure {
	private $time;
	private $weapon;

	private $shooterPosition;
	private $victimPosition;
	private $shooter;
	private $victim;

	private $distance;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj        = $this->getPlainJsonObject();
		$this->time     = $jsonObj->time;
		$this->weapon   = $jsonObj->weapon;
		$this->distance = $jsonObj->distance;

		$this->shooterPosition = new Position();
		$this->shooterPosition->setX($jsonObj->shooterposition->x);
		$this->shooterPosition->setY($jsonObj->shooterposition->y);
		$this->shooterPosition->setZ($jsonObj->shooterposition->z);

		$this->victimPosition = new Position();
		$this->victimPosition->setX($jsonObj->victimposition->x);
		$this->victimPosition->setY($jsonObj->victimposition->y);
		$this->victimPosition->setZ($jsonObj->victimposition->z);

		$this->shooter = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->shooter);
		$this->victim  = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->victim);
	}

	/**
	 * < Server time when the event occured
	 *
	 * @api
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * < Id of the weapon [1-Laser, 2-Rocket, 3-Nucleus, 5-Arrow]
	 *
	 * @api
	 * @see \ManiaControl\Callbacks\Structures\ShootMania\Models\Weapons
	 * @return int
	 */
	public function getWeapon() {
		return intval($this->weapon);
	}

	/**
	 * < Position of the Shooter at the time
	 *
	 * @api
	 * @return Position
	 */
	public function getShooterPosition() {
		return $this->shooterPosition;
	}

	/**
	 * < Position of the Victim at the time
	 *
	 * @api
	 * @return \ManiaControl\Callbacks\Structures\ShootMania\Models\Position
	 */
	public function getVictimPosition() {
		return $this->victimPosition;
	}

	/**
	 * < Shooter Player
	 *
	 * @api
	 * @return Player
	 */
	public function getShooter() {
		return $this->shooter;
	}

	/**
	 * < Victim Player
	 *
	 * @api
	 * @return Player
	 */
	public function getVictim() {
		return $this->victim;
	}

	/**
	 * Distance Between Shooter and Victim at the time of the Event
	 *
	 * @api
	 * @return float
	 */
	public function getDistance() {
		return $this->distance;
	}
}