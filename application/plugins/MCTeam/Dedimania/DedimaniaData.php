<?php

namespace MCTeam\Dedimania;

use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Structures\Version;

/**
 * ManiaControl Dedimania Plugin DataStructure
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DedimaniaData {
	/*
	 * Constants
	 */
	public $game;
	public $path;
	public $packmask;
	public $serverVersion;
	public $serverBuild;
	public $tool;
	public $version;
	public $login;
	public $code;
	public $sessionId = '';
	public $records = array();
	public $players = array();
	public $directoryAccessChecked = false;
	public $serverMaxRank = 30;

	/**
	 * Construct a new Dedimania Data Model
	 *
	 * @param string  $serverLogin
	 * @param string  $dedimaniaCode
	 * @param string  $path
	 * @param string  $packmask
	 * @param Version $serverVersion
	 */
	public function __construct($serverLogin, $dedimaniaCode, $path, $packmask, Version $serverVersion) {
		$this->game          = "TM2";
		$this->login         = $serverLogin;
		$this->code          = $dedimaniaCode;
		$this->version       = ManiaControl::VERSION;
		$this->tool          = "ManiaControl";
		$this->path          = $path;
		$this->packmask      = $packmask;
		$this->serverVersion = $serverVersion->version;
		$this->serverBuild   = $serverVersion->build;
	}

	public function toArray() {
		$array = array();
		foreach (get_object_vars($this) as $key => $value) {
			if ($key == 'records' || $key == 'sessionId' || $key == 'directoryAccessChecked' || $key == 'serverMaxRank' || $key == 'players') {
				continue;
			}
			$array[ucfirst($key)] = $value;
		}
		return $array;
	}

	public function getRecordCount() {
		return count($this->records);
	}

	/**
	 * Get Max Rank for a certain Player
	 *
	 * @param $login
	 * @return int
	 */
	public function getPlayerMaxRank($login) {
		$maxRank = $this->serverMaxRank;
		foreach ($this->players as $player) {
			/** @var DedimaniaPlayer $player */
			if ($player->login === $login) {
				if ($player->maxRank > $maxRank) {
					$maxRank = $player->maxRank;
				}
				break;
			}
		}
		return $maxRank;
	}

	/**
	 * Add a Player to the Players array
	 *
	 * @param DedimaniaPlayer $player
	 */
	public function addPlayer(DedimaniaPlayer $player) {
		/** @var DedimaniaPlayer $player */
		$this->players[$player->login] = $player;
	}

	/**
	 * Remove a Dedimania Player by its login
	 *
	 * @param string $login
	 */
	public function removePlayer($login) {
		unset($this->players[$login]);
	}
} 