<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnHit Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnHitStructure extends OnHitNearMissArmorEmptyBaseStructure {
	private $damage;
	private $shooterPoints;

	/**
	 * OnHitStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param array                      $data
	 */
	public function __construct(ManiaControl $maniaControl, array $data) {
		parent::__construct($maniaControl, $data);

		$this->damage        = $this->getPlainJsonObject()->damage;
		$this->shooterPoints = $this->getPlainJsonObject()->points;
	}

	/**
	 * < Amount of Damage done by the hit (only on onHit)
	 *
	 * @api
	 * @return int
	 */
	public function getDamage() {
		return $this->damage;
	}

	/**
	 * Amount of points scored by the shooter
	 *
	 * @api
	 * @return int
	 */
	public function getShooterPoints() {
		return $this->shooterPoints;
	}

}