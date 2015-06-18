<?php

namespace ManiaControl\Callbacks\Structures;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Structure Class for the Player Hit Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2015 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
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
	private $hitDistance = 0;

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

		//TODO remove key check in some months (hitDistance got implemented 2014-10-16)
		if (array_key_exists(5, $data)) {
			$this->hitDistance = $data[5];
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
