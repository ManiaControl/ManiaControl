<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnJoustSelectedPlayers Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnJoustSelectedPlayersStructure extends BaseStructure {
	protected $playerArray;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->playerArray = $this->getPlainJsonObject()->players;
	}


	/**
	 * Returns a Login Array of the Players
	 *
	 * @api
	 * @return array
	 */
	public function getPlayerLogins() {
		return $this->playerArray;
	}

	/**
	 * Gets an Array of the Players
	 *
	 * @api
	 * @return \ManiaControl\Players\Player[]
	 */
	public function getPlayers() {
		$players = array();
		foreach ($this->playerArray as $login) {
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
			if ($player) {
				$players[$login] = $player;
			}
		}
		return $players;
	}
}