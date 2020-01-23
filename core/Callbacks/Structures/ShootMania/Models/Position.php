<?php


namespace ManiaControl\Callbacks\Structures\ShootMania\Models;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * Position Model
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Position implements UsageInformationAble {
	use UsageInformationTrait;

	private $x = 0;
	private $y = 0;
	private $z = 0;

	/**
	 * @api
	 * @return int
	 */
	public function getX() {
		return $this->x;
	}

	/**
	 * @api
	 * @param int $x
	 */
	public function setX($x) {
		$this->x = $x;
	}

	/**
	 * @api
	 * @return int
	 */
	public function getZ() {
		return $this->z;
	}

	/**
	 * @api
	 * @param int $z
	 */
	public function setZ($z) {
		$this->z = $z;
	}

	/**
	 * @api
	 * @return int
	 */
	public function getY() {
		return $this->y;
	}

	/**
	 * @api
	 * @param int $y
	 */
	public function setY($y) {
		$this->y = $y;
	}
}