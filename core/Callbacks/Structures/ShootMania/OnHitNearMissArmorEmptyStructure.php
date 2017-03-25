<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\Callbacks\Structures\ShootMania\Models\Position;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;


/**
 * Structure Class for the OnHit Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnHitNearMissArmorEmptyStructure extends BaseStructure {
	public $time;
	public $weapon;
	public $damage;
	public $shooterPosition;
	public $victimPosition;
	public $distance = 0; //Note no distance on the OnHit and ArmorEmpty yet

	protected $shooter;
	protected $victim;

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

		$jsonObj = $this->getPlainJsonObject();
		$this->time            = $jsonObj->time;
		$this->weapon          = $jsonObj->weapon;
		$this->damage          = $jsonObj->damage;

		$this->shooterPosition = new Position();
		$this->shooterPosition->setX($jsonObj->shooterposition->x);
		$this->shooterPosition->setY($jsonObj->shooterposition->y);
		$this->shooterPosition->setZ($jsonObj->shooterposition->z);

		$this->victimPosition = new Position();
		$this->victimPosition->setX($jsonObj->victimposition->x);
		$this->victimPosition->setY($jsonObj->victimposition->y);
		$this->victimPosition->setZ($jsonObj->victimposition->z);

		if (property_exists($this->getPlainJsonObject(), 'distance')) {
			$this->distance = $this->getPlainJsonObject()->distance;
		}

		$this->shooter = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->shooter);
		$this->victim  = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->victim);
	}

	/**
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * @return int
	 */
	public function getWeapon() {
		return $this->weapon;
	}

	/**
	 * @return int
	 */
	public function getDamage() {
		return $this->damage;
	}

	/**
	 * TODO Position Object
	 *
	 * @return Position
	 */
	public function getShooterPosition() {
		return $this->shooterPosition;
	}

	/**
	 * TODO Position Object
	 *
	 * @return Position
	 */
	public function getVictimPosition() {
		return $this->victimPosition;
	}

	/**
	 * @return Player
	 */
	public function getShooter() {
		return $this->shooter;
	}

	/**
	 * @return Player
	 */
	public function getVictim() {
		return $this->victim;
	}

	/**
	 * @return mixed
	 */
	public function getDistance() {
		return $this->distance;
	}

	/** Dumps the Object with some Information */
	public function dump() {
		parent::dump();
		var_dump("With getShooter() you get a Player Object");
		var_dump("With getVictim() you get a Player Object");
	}
}