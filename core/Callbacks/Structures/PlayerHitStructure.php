<?php

namespace ManiaControl\Callbacks\Structures;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

class PlayerHitStructure {
	/*
	 * Private properties
	 */
	private $shooter;
	private $victim;
	private $damage;
	private $shooterPoints;
	private $weapon;
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
		return $this->damage;
	}

	/**
	 * Get the shooter points
	 *
	 * @return int
	 */
	public function getShooterPoints() {
		return $this->shooterPoints;
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
}
