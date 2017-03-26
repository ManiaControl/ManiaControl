<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Structure Class for the OnShoot Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnShootStructure extends BaseStructure {
	public $time;
	public $weapon;
	/**
	 * @var Player $shooter
	 */
	private $shooter;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time    = $this->getPlainJsonObject()->time;
		$this->weapon  = $this->getPlainJsonObject()->weapon;

		$this->shooter = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->shooter);

	}

	/**
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * @return int
	 */
	public function getWeapon() {
		return $this->weapon;
	}

	/**
	 * @return Player
	 */
	public function getShooter() {
		return $this->shooter;
	}

	/** Dumps the Object with some Information */
	public function dump() {
		parent::dump();
		var_dump("With getShooter() you get a Player Object");
	}
}