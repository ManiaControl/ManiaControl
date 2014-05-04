<?php

namespace MCTeam\Dedimania;

/**
 * ManiaControl Dedimania-Plugin Player DataStructure
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DedimaniaPlayer {
	/*
	 * Public Properties
	 */
	public $login = '';
	public $maxRank = -1;
	public $banned = false;
	public $optionsEnabled = false;
	public $options = '';

	/**
	 * Construct a new Dedimania Player Model
	 * @param mixed$player
	 */
	public function __construct($player) {
		if (!$player) {
			return;
		}

		$this->login          = $player['Login'];
		$this->maxRank        = $player['MaxRank'];
		$this->banned         = $player['Banned'];
		$this->optionsEnabled = $player['OptionsEnabled'];
		$this->options        = $player['Options'];
	}

	/**
	 * Construct a new Player by its login and maxRank
	 *
	 * @param string $login
	 * @param int $maxRank
	 */
	public function constructNewPlayer($login, $maxRank) {
		$this->login   = $login;
		$this->maxRank = $maxRank;
	}
} 