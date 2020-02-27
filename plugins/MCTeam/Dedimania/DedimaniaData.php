<?php

namespace MCTeam\Dedimania;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Structures\Version;

/**
 * ManiaControl Dedimania Plugin Data Structure
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
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
	/** @var RecordData[] $records */
	public $records = array();
	/** @var DedimaniaPlayer[] $players */
	public $players                = array();
	public $directoryAccessChecked = null;
	public $serverMaxRank          = 30;

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
		$this->game          = 'TM2';
		$this->login         = $serverLogin;
		$this->code          = $dedimaniaCode;
		$this->version       = ManiaControl::VERSION;
		$this->tool          = 'ManiaControl';
		$this->path          = $path;
		$this->packmask      = $packmask;
		$this->serverVersion = $serverVersion->version;
		$this->serverBuild   = $serverVersion->build;
	}

	public function sessionIdSet(){
		return $this->sessionId != '';
	}

	/**
	 * Sort the Records
	 */
	public function sortRecords() {
		usort($this->records, function (RecordData $first, RecordData $second) {
			if ($first->best == $second->best) {
				return ($first->rank - $second->rank);
			}
			return ($first->best - $second->best);
		});
	}

	/**
	 * Update the Record Ranks
	 */
	public function updateRanks() {
		$rank = 1;
		foreach ($this->records as $record) {
			$record->rank = $rank;
			$rank++;
		}
	}

	/**
	 * Build the Data Array
	 *
	 * @return array
	 */
	public function toArray() {
		$array = array();
		foreach (get_object_vars($this) as $key => $value) {
			if ($key === 'records' || $key === 'sessionId' || $key === 'directoryAccessChecked' || $key === 'serverMaxRank' || $key === 'players') {
				continue;
			}
			$array[ucfirst($key)] = $value;
		}
		return $array;
	}

	/**
	 * Get the Number of Records
	 *
	 * @return int
	 */
	public function getRecordCount() {
		return count($this->records);
	}

	/**
	 * Checks if a Record is in the array
	 *
	 * @return bool
	 */
	public function recordsExisting() {
		return $this->getRecordCount() > 0;
	}

	/**
	 * Unsets all existing Records
	 */
	public function unsetRecords() {
		$this->records = array();
	}

	/**
	 * Deletes a Record by its Index
	 *
	 * @param $index
	 */
	public function deleteRecordByIndex($index) {
		unset($this->records[$index]);
	}

	/**
	 * Get Max Rank for a certain Player
	 *
	 * @param mixed $login
	 * @return int
	 */
	public function getPlayerMaxRank($login) {
		$login   = Player::parseLogin($login);
		$maxRank = $this->serverMaxRank;
		foreach ($this->players as $player) {
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
