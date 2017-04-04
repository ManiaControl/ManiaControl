<?php

namespace ManiaControl\Callbacks\Structures;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Structure Class for the Player Hit Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 *
 * @deprecated see OnPlayerHitStructure
 */
class PlayerHitStructure {
	/*
	 * Private properties
	 */
	private $shooter;
	private $victim;
	private $damage;
	private $shooterPoints;
	private $weapon;
	private $hitDistance;
	private $shooterPosition     = 0;
	private $victimPosition      = 0;
	private $shooterAimDirection = 0;
	private $victimAimDirection  = 0;

	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	/**
	 * Construct new Player Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, array $data) {
		$this->maniaControl  = $maniaControl;
		$this->shooter       = $data[0];
		$this->victim        = $data[1];
		$this->damage        = $data[2];
		$this->weapon        = $data[3];
		$this->shooterPoints = $data[4];
		$this->hitDistance   = $data[5];

		//TODO remove key check in some months (got implemented 2015-05-03)
		if (array_key_exists(6, $data)) {
			$this->shooterPosition = $data[6];
		}

		if (array_key_exists(7, $data)) {
			$this->victimPosition = $data[7];
		}

		if (array_key_exists(8, $data)) {
			$this->shooterAimDirection = $data[8];
		}

		if (array_key_exists(9, $data)) {
			$this->victimAimDirection = $data[9];
		}
	}

	/**
	 * Get the shooter
	 *
	 * @return Player
	 */
	public function getShooter() {
		return $this->maniaControl->getPlayerManager()->getPlayer($this->shooter);
	}

	/**
	 * Get the victim
	 *
	 * @return Player
	 */
	public function getVictim() {
		return $this->maniaControl->getPlayerManager()->getPlayer($this->victim);
	}

	/**
	 * Get the damage
	 *
	 * @return int
	 */
	public function getDamage() {
		return intval($this->damage);
	}

	/**
	 * Get the shooter points
	 *
	 * @return int
	 */
	public function getShooterPoints() {
		return intval($this->shooterPoints);
	}

	/**
	 * Get the weapon
	 *
	 * @return int
	 */
	public function getWeapon() {
		// TODO: any way of returning type "Weapon?"
		return $this->weapon;
	}

	/**
	 * Get The Hit Distance
	 *
	 * @return double
	 */
	public function getHitDistance() {
		return doubleval($this->hitDistance);
	}
}
