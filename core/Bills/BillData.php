<?php

namespace ManiaControl\Bills;

use ManiaControl\Players\Player;

/**
 * ManiaControl BillData Structure
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2015 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class BillData {
	/*
	 * Public properties
	 */
	public $function = null;
	public $pay = false;
	public $player = null;
	public $receiverLogin = null;
	public $amount = 0;
	public $creationTime = -1;

	/**
	 * Construct new Bill Data Model
	 *
	 * @param callable      $function
	 * @param Player|string $player
	 * @param int           $amount
	 * @param bool          $pay
	 * @param string        $receiverLogin
	 */
	public function __construct(callable $function, $player, $amount, $pay = false, $receiverLogin = null) {
		$this->function      = $function;
		$this->player        = $player;
		$this->amount        = $amount;
		$this->pay           = $pay;
		$this->receiverLogin = $receiverLogin;
		$this->creationTime  = time();
	}
}
