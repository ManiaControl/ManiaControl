<?php

namespace ManiaControl\Callbacks\Structures\ShootMania\Models;

//TODO describtion
class TeamScore {
	private $id;
	private $name;
	private $roundPoints;
	private $mapPoints;
	private $matchPoints;

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
	public function getName() {
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getRoundPoints() {
		return $this->roundPoints;
	}

	/**
	 * @param mixed $roundPoints
	 */
	public function setRoundPoints($roundPoints) {
		$this->roundPoints = $roundPoints;
	}

	/**
	 * @return mixed
	 */
	public function getMapPoints() {
		return $this->mapPoints;
	}

	/**
	 * @param mixed $mapPoints
	 */
	public function setMapPoints($mapPoints) {
		$this->mapPoints = $mapPoints;
	}

	/**
	 * @return mixed
	 */
	public function getMatchPoints() {
		return $this->matchPoints;
	}

	/**
	 * @param mixed $matchPoints
	 */
	public function setMatchPoints($matchPoints) {
		$this->matchPoints = $matchPoints;
	}
}