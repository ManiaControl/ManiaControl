<?php

namespace ManiaControl\Callbacks\Structures;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Structure Class for the NearMiss Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class NearMissStructure {
	/*
	 * Private properties
	 */
	private $shooter;
	private $victim;
	private $distance;
	private $weapon;
	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	/**
	 * Construct a new Near Miss Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, array $data) {
		$this->maniaControl = $maniaControl;
		$this->shooter      = $data[0];
		$this->victim       = $data[1];
		$this->weapon       = $data[2];
		$this->distance     = $data[3];
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
	 * Get the distance
	 *
	 * @return double
	 */
	public function getDistance() {
		return doubleval($this->distance);
	}

	/**
	 * Get the weapon
	 *
	 * @return int
	 */
	public function getWeapon() {
		return $this->weapon;
	}

}
