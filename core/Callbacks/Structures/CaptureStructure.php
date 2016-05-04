<?php

namespace ManiaControl\Callbacks\Structures;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Structure Class for the Capture Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2016 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CaptureStructure {
	/*
	 * Private properties
	 */
	private $playerArray;
	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	/**
	 * Construct a new Capture Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, array $data) {
		$this->maniaControl = $maniaControl;
		$this->playerArray  = $data;
	}

	/**
	 * Get the logins
	 *
	 * @return array
	 */
	public function getLoginArray() {
		return $this->playerArray;
	}

	/**
	 * Get the players
	 *
	 * @return Player[]
	 */
	public function getPlayerArray() {
		$playerArray = array();
		foreach ($this->playerArray as $login) {
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
			if ($player) {
				$playerArray[$login] = $player;
			}
		}
		return $playerArray;
	}
}
