<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 23.02.14
 * Time: 12:45
 */

namespace Dedimania;


class DedimaniaPlayer {
	public $login = '';
	public $maxRank = -1;
	public $banned = false;
	public $optionsEnabled = false;
	public $options = '';

	public function __construct($player) {
		if ($player == null) {
			return;
		}

		$this->login          = $player['Login'];
		$this->maxRank        = $player['MaxRank'];
		$this->banned         = $player['Banned'];
		$this->optionsEnabled = $player['OptionsEnabled'];
		$this->options     = $player['Options'];
	}

	/**
	 * Construct a new Player by its login and maxRank
	 * @param $login
	 * @param $maxRank
	 */
	public function constructNewPlayer($login, $maxRank){
		$this->login = $login;
		$this->maxRank = $maxRank;
	}
} 