<?php

namespace ManiaControl\Callbacks\Structures;


use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

class ArmorEmptyStructure {
	private $shooter;
	private $victim;
	private $damage;
	private $shooterPoints;
	private $weapon;
	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	public function __construct(ManiaControl $maniaControl, $data) {
		$this->shooter       = $data[0];
		$this->victim        = $data[1];
		$this->damage        = $data[2];
		$this->weapon        = $data[3];
		$this->shooterPoints = $data[4];
	}

	/**
	 * @return Player
	 */
	public function getShooter() {
		$shooter = $this->maniaControl->getPlayerManager()->getPlayer($this->shooter);
		return $shooter;
	}

	/**
	 * @return Player
	 */
	public function getVictim() {
		$victim = $this->maniaControl->getPlayerManager()->getPlayer($this->victim);
		return $victim;
	}

	/**
	 * @return int
	 */
	public function getDamage() {
		return $this->damage;
	}

	/**
	 * @return int
	 */
	public function getShooterPoints() {
		return $this->shooterPoints;
	}

	/**
	 * @return int
	 */
	public function getWeapon() { //TODO any way of returning type "Weapon?"
		return $this->weapon;
	}
}