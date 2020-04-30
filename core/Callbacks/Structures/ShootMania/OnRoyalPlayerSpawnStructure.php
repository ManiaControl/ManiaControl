<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnRoyalPlayerSpawn Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnRoyalPlayerSpawnStructure extends BaseStructure {
	protected $player;
	protected $respawn;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->player  = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->login);
		$this->respawn = $this->getPlainJsonObject()->respawn;
	}

	/**
	 * Gets the Player
	 *
	 * @api
	 * @return \ManiaControl\Players\Player Player
	 */
	public function getPlayer() {
		return $this->player;
	}

	/**
	 * @api
	 * @return boolean < false if it is the initial player spawn in the round. true if it is a respawn.
	 */
	public function getRespawn() {
		return $this->respawn;
	}
}