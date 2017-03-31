<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Structure Class for the OnPlayerRequestRespawnStructure Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnPlayerRequestRespawnStructure extends BaseStructure {
	private $time;
	/**
	 * @var Player $player
	 */
	private $player;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time   = $this->getPlainJsonObject()->time;
		$this->player = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->login);

	}

	/**
	 * Returns the Time the Event Happened
	 *
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * Gets the Player
	 *
	 * @return \ManiaControl\Players\Player
	 */
	public function getPlayer() {
		return $this->player;
	}
}