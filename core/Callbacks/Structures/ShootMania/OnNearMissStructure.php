<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnNearMiss Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnNearMissStructure extends OnHitNearMissArmorEmptyBaseStructure {
	private $distance;

	public function __construct(ManiaControl $maniaControl, array $data) {
		parent::__construct($maniaControl, $data);

		$this->distance = $this->getPlainJsonObject()->distance;
	}

	/**
	 * Returns the distance
	 *
	 * @return float
	 */
	public function getDistance() {
		return $this->distance;
	}
}