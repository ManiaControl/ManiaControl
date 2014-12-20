<?php

namespace ManiaControl\Callbacks\Structures;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

class EliteBeginTurnStructure {
	/*
	 * Private properties
	 */
	private $attackerLogin;
	private $defenderLogins;

	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	/**
	 * Construct a new Elite BeginTurnStructure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, array $data) {
		$this->maniaControl    = $maniaControl;
		$this->attackerLogin   = $data[0];
		$this->defenderLogins = $data[1];
	}

	/**
	 * Get the attacker
	 *
	 * @return Player
	 */
	public function getAttacker() {
		return $this->maniaControl->getPlayerManager()->getPlayer($this->attackerLogin);
	}

	/**
	 * Get the defenders as an Player Array
	 *
	 * @return Player[]
	 */
	public function getDefenders() {
		$defenders = array();
		foreach (explode(";", $this->defenderLogins) as $defenderLogin) {
			$defenders[] = $this->maniaControl->getPlayerManager()->getPlayer($defenderLogin);
		}

		return $defenders;
	}
}
