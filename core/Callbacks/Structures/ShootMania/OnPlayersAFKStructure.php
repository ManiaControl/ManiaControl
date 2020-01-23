<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;

use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the AFK.IsAFK Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnPlayersAFKStructure extends BaseStructure {
	protected $logins;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj      = $this->getPlainJsonObject();
		$this->logins = $jsonObj->logins;
	}

	/**
	 * Returns a Login Array of the defenders
	 *
	 * @api
	 * @return array
	 */
	public function getAFKPlayerLogins() {
		return $this->logins;
	}

	/**
	 * Gets an Array of the Players
	 *
	 * @api
	 * @return \ManiaControl\Players\Player[]
	 */
	public function getAFKPlayers() {
		$afkPlayers = array();
		foreach ($this->logins as $login) {
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
			if ($player) {
				$afkPlayers[$login] = $player;
			}
		}
		return $afkPlayers;
	}
}