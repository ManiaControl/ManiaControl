<?php

namespace ManiaControl\Callbacks\Structures;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

class CaptureStructure {
	/*
	 * Private properties
	 */
	private $playerArray;
	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	/**
	 * Construct a new Capture Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, array $data) {
		$this->maniaControl = $maniaControl;
		$this->playerArray  = $data;
	}

	/**
	 * Get the logins
	 *
	 * @return array
	 */
	public function getLoginArray() {
		return $this->playerArray;
	}

	/**
	 * Get the players
	 *
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
