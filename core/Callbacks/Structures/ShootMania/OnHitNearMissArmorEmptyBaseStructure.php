<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\Callbacks\Structures\ShootMania\Models\Position;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;


/**
 * Structure Base Class for the OnHit/OnNearMiss/OnArmorEmpty Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnHitNearMissArmorEmptyBaseStructure extends BaseStructure {
	private $time;
	private $weapon;

	private $shooterPosition;
	private $victimPosition;
	private $shooter;
	private $victim;

	//private $shooterPoints; (was in mp3)
	//private $hitDistance; (was in mp3)


	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj      = $this->getPlainJsonObject();
		$this->time   = $jsonObj->time;
		$this->weapon = $jsonObj->weapon;

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
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * < Id of the weapon [1-Laser, 2-Rocket, 3-Nucleus, 5-Arrow]
	 *
	 * @see \ManiaControl\Callbacks\Structures\ShootMania\Models\Weapons
	 * @return int
	 */
	public function getWeapon() {
		return $this->weapon;
	}

	/**
	 * < Position of the Shooter at the time
	 *
	 * @return Position
	 */
	public function getShooterPosition() {
		return $this->shooterPosition;
	}

	/**
	 * < Position of the Victim at the time
	 *
	 * @return \ManiaControl\Callbacks\Structures\ShootMania\Models\Position
	 */
	public function getVictimPosition() {
		return $this->victimPosition;
	}

	/**
	 * < Shooter Player
	 *
	 * @return Player
	 */
	public function getShooter() {
		return $this->shooter;
	}

	/**
	 * < Victim Player
	 *
	 * @return Player
	 */
	public function getVictim() {
		return $this->victim;
	}



}