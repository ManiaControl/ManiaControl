<?php

namespace ManiaControl\Callbacks\Structures;


use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

class NearMissStructure {
	private $shooter;
	private $victim;
	private $distance;
	private $weapon;

	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	public function __construct(ManiaControl $maniaControl, $data) {
		$this->shooter  = $data[0];
		$this->victim   = $data[1];
		$this->weapon   = $data[2];
		$this->distance = $data[3];
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
	 * @return mixed
	 */
	public function getDistance() {
		return $this->distance;
	}

	/**
	 * @return mixed
	 */
	public function getWeapon() {
		return $this->weapon;
	}

}