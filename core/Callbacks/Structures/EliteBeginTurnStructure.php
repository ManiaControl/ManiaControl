<?php

namespace ManiaControl\Callbacks\Structures;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Structure Class for the EliteBeginTurn Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
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
		$this->maniaControl   = $maniaControl;
		$this->attackerLogin  = $data[0];
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
