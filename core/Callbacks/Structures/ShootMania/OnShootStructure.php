<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
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
	private $shooterLogin;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time         = $this->getPlainJsonObject()->time;
		$this->weapon       = $this->getPlainJsonObject()->weapon;
		$this->shooterLogin = $this->getPlainJsonObject()->shooter;
	}

	/**
	 * Gets the Time the event Happened
	 *
	 * @api
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * Gets the Weapon
	 *
	 * @api
	 * @see \ManiaControl\Callbacks\Structures\ShootMania\Models\Weapons
	 * @return int
	 */
	public function getWeapon() {
		return $this->weapon;
	}

	/**
	 * Gets the Shooter
	 *
	 * @api
	 * @return Player
	 */
	public function getShooter() {
		return $this->maniaControl->getPlayerManager()->getPlayer($this->shooterLogin);
	}
}