<?php


namespace ManiaControl\Callbacks\Structures\ShootMania\Models;

//TODO describtion
class Position {
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