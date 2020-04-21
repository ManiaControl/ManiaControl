<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;



use ManiaControl\Callbacks\Structures\Common\BaseTimeStructure;
use ManiaControl\ManiaControl;


/**
 * Structure Class for the OnShotDeny Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnShotDenyStructure extends BaseTimeStructure {
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
		$this->shooterWeapon = $jsonObj->shooterweapon;
		$this->victimWeapon  = $jsonObj->victimweapon;

		$this->shooter = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->shooter);
		$this->victim  = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->victim);
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