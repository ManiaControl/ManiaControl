<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnEliteStartTurn Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnEliteStartTurnStructure extends BaseStructure {
	protected $attacker;
	protected $defenderArray;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj             = $this->getPlainJsonObject();
		$this->attacker      = $this->maniaControl->getPlayerManager()->getPlayer($jsonObj->attacker);
		$this->defenderArray = $jsonObj->defenders;
	}

	/**
	 * @return \ManiaControl\Players\Player
	 */
	public function getAttacker() {
		return $this->attacker;
	}

	/**
	 * Returns a Login Array of the defenders
	 *
	 * @return array
	 */
	public function getDefenderLogins() {
		return $this->defenderArray;
	}

	/**
	 * Gets an Array of the Players
	 *
	 * @return \ManiaControl\Players\Player[]
	 */
	public function getDefenders() {
		$defenders = array();
		foreach ($this->defenderArray as $login) {
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
			if ($player) {
				$defenders[$login] = $player;
			}
		}
		return $defenders;
	}
}