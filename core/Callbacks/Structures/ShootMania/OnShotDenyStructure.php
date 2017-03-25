<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;


/**
 * Structure Class for the OnShotDeny Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnShotDenyStructure extends BaseStructure {
	public $time;
	public $shooterWeapon;
	public $victimWeapon;

	protected $shooter;
	protected $victim;

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

	/** Dumps the Object with some Information */
	public function dump() {
		parent::dump();
		var_dump("With getShooter() you get a Player Object");
		var_dump("With getVictim() you get a Player Object");
	}

	/**
	 * @return int
	 */
	public function getTime() {
		return $this->time;
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
	public function getShooterWeapon() {
		return $this->shooterWeapon;
	}

	/**
	 * @return mixed
	 */
	public function getVictimWeapon() {
		return $this->victimWeapon;
	}


}