<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\BaseStructure;
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
	public $time;
	/**
	 * @var Player $shooter
	 */
	private $player;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time   = $this->getPlainJsonObject()->time;
		$this->player = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->login);
	}

	/**
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * @return Player
	 */
	public function getPlayer() {
		return $this->player;
	}

	/** Dumps the Object with some Information */
	public function dump() {
		parent::dump();
		var_dump("With getPlayer() you get a Player Object");
	}
}