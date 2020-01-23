<?php

namespace MCTeam\Dedimania;

use ManiaControl\Utils\Formatter;

/**
 * ManiaControl Dedimania Plugin Record Data Structure
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class RecordData {
	/*
	 * Public properties
	 */
	public $nullRecord = false;
	public $login = '';
	public $nickname = '';
	public $best = -1;
	public $rank = -1;
	public $maxRank = -1;
	public $checkpoints = '';
	public $newRecord = false;
	public $vReplay = '';
	public $top1GReplay = '';
	public $allCheckpoints = '';

	/**
	 * Construct a Record by a given Record Array
	 *
	 * @param array $record
	 */
	public function __construct($record) {
		if (!$record) {
			$this->nullRecord = true;
			return;
		}

		$this->login       = $record['Login'];
		$this->nickname    = Formatter::stripDirtyCodes($record['NickName']);
		$this->best        = $record['Best'];
		$this->rank        = $record['Rank'];
		$this->maxRank     = $record['MaxRank'];
		$this->checkpoints = $record['Checks'];
	}

	/**
	 * Construct a new Record via its Properties
	 *
	 * @param string $login
	 * @param string $nickName
	 * @param float  $best
	 * @param int    $checkpoints
	 * @param bool   $newRecord
	 */
	public function constructNewRecord($login, $nickname, $best, $checkpoints, $newRecord = false, $allCPs = '') {
		$this->nullRecord  = false;
		$this->login       = $login;
		$this->nickname    = $nickname;
		$this->best        = $best;
		$this->checkpoints = $checkpoints;
		$this->newRecord   = $newRecord;
		$this->allCheckpoints = $allCPs;
	}
}
