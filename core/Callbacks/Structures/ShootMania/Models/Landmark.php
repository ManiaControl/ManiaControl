<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 25. Mär. 2017
 * Time: 11:37
 */

namespace ManiaControl\Callbacks\Structures\ShootMania\Models;


class Landmark {
	private $tag      = "";
	private $order    = 0;
	private $id       = "";
	private $position = null;

	/**
	 * @return mixed
	 */
	public function getTag() {
		return $this->tag;
	}

	/**
	 * @param mixed $tag
	 */
	public function setTag($tag) {
		$this->tag = $tag;
	}

	/**
	 * @return mixed
	 */
	public function getOrder() {
		return $this->order;
	}

	/**
	 * @param mixed $order
	 */
	public function setOrder($order) {
		$this->order = $order;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @return mixed
	 */
	public function getPosition() {
		return $this->position;
	}

	/**
	 * @param mixed $position
	 */
	public function setPosition(Position $position) {
		$this->position = $position;
	}
}