<?php

namespace MCTeam\Dedimania;

/**
 * ManiaControl Dedimania Plugin Player Data Structure
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DedimaniaPlayer {
	/*
	 * Public properties
	 */
	public $login = '';
	public $maxRank = -1;
	public $banned = false;
	public $optionsEnabled = false;
	public $options = '';

	/**
	 * Construct a new Dedimania Player Model
	 *
	 * @param mixed $player
	 */
	public function __construct($player) {
		$this->login   = $player['Login'];
		$this->maxRank = $player['MaxRank'];
		if (isset($player['Banned'])) {
			$this->banned         = $player['Banned'];
			$this->optionsEnabled = $player['OptionsEnabled'];
			$this->options        = $player['Options'];
		}
	}
} 