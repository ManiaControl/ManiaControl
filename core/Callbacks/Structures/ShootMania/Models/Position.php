<?php


namespace ManiaControl\Callbacks\Structures\ShootMania\Models;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * Position Model
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Position implements UsageInformationAble {
	use UsageInformationTrait;

	private $x = 0;
	private $y = 0;
	private $z = 0;

	/**
	 * @return int
	 */
	public function getX() {
		return $this->x;
	}

	/**
	 * @param int $x
	 */
	public function setX($x) {
		$this->x = $x;
	}

	/**
	 * @return int
	 */
	public function getZ() {
		return $this->z;
	}

	/**
	 * @param int $z
	 */
	public function setZ($z) {
		$this->z = $z;
	}

	/**
	 * @return int
	 */
	public function getY() {
		return $this->y;
	}

	/**
	 * @param int $y
	 */
	public function setY($y) {
		$this->y = $y;
	}
}