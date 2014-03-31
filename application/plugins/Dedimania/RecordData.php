<?php
/**
 * Dedimania Record DataStructure
 *
 * @author kremsy and steeffeen
 */
namespace Dedimania;


use ManiaControl\Formatter;

class RecordData {
	public $nullRecord = false;
	public $login = '';
	public $nickName = '';
	public $best = -1;
	public $rank = -1;
	public $maxRank = -1;
	public $checkpoints = '';
	public $newRecord = false;
	public $vReplay = '';
	public $top1GReplay = '';

	/**
	 * Construct a Record by a given Record Array
	 *
	 * @param $record
	 */
	public function __construct($record) {
		if (!$record) {
			$this->nullRecord = true;
			return;
		}

		$this->login       = $record['Login'];
		$this->nickName    = Formatter::stripDirtyCodes($record['NickName']);
		$this->best        = $record['Best'];
		$this->rank        = $record['Rank'];
		$this->maxRank     = $record['MaxRank'];
		$this->checkpoints = $record['Checks'];
	}

	/**
	 * Constructs a new Record via it's properties
	 *
	 * @param      $login
	 * @param      $nickName
	 * @param      $best
	 * @param      $checkpoints
	 * @param bool $newRecord
	 */
	public function constructNewRecord($login, $nickName, $best, $checkpoints, $newRecord = false) {
		$this->nullRecord  = false;
		$this->login       = $login;
		$this->nickName    = $nickName;
		$this->best        = $best;
		$this->checkpoints = $checkpoints;
		$this->newRecord   = $newRecord;
	}
} 