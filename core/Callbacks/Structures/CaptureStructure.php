<?php

namespace ManiaControl\Callbacks\Structures;


use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

class CaptureStructure {
	private $playerArray;
	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	public function __construct(ManiaControl $maniaControl, $data) {
		$this->maniaControl = $maniaControl;
		$this->playerArray  = $data;
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
		$playerArray = array();
		foreach ($this->playerArray as $login) {
			$playerArray[$login] = $this->maniaControl->getPlayerManager()->getPlayer($this->playerArray);
		}
		return $playerArray;
	}
}