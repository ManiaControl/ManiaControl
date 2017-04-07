<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;


/**
 * Structure Class for the OnShotDeny Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnShotDenyStructure extends BaseStructure {
	private $time;
	private $shooterWeapon;
	private $victimWeapon;

	private $shooter;
	private $victim;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj             = $this->getPlainJsonObject();
		$this->time          = $jsonObj->time;
		$this->shooterWeapon = $jsonObj->victim;
		$this->victimWeapon  = $jsonObj->damage;

		$this->shooter = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->shooter);
		$this->victim  = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->victim);
	}

	/**
	 * ServerTime The Event Happened //TODO add Trait for the Time Property
	 *
	 * @api
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}


	/**
	 * Gets the Shooter Player
	 *
	 * @api
	 * @return \ManiaControl\Players\Player
	 */
	public function getShooter() {
		return $this->shooter;
	}

	/**
	 * Gets the Victim Player
	 *
	 * @api
	 * @return \ManiaControl\Players\Player
	 */
	public function getVictim() {
		return $this->victim;
	}

	/**
	 * Gets the Shooter Weapon
	 *
	 * @api
	 * @see \ManiaControl\Callbacks\Structures\ShootMania\Models\Weapons
	 * @return int Weapon
	 */
	public function getShooterWeapon() {
		return $this->shooterWeapon;
	}

	/**
	 * Get the Victim Weapon
	 *
	 * @api
	 * @see \ManiaControl\Callbacks\Structures\ShootMania\Models\Weapons
	 * @return int
	 */
	public function getVictimWeapon() {
		return $this->victimWeapon;
	}


}