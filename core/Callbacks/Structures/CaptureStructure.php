<?php

namespace ManiaControl\Callbacks\Structures;


use ManiaControl\Players\Player;

class CaptureStructure {
	private $playerArray;

	public function __construct($maniaControl, $data) {
		$this->playerArray = $data;
	}

	/**
	 * @return mixed
	 */
	public function getLoginArray() {
		return $this->playerArray;
	}

	/**
	 * @return Player[]
	 */
	public function getPlayerArray() {
		//TODO build array with player objects
		return $this->playerArray;
	}
}