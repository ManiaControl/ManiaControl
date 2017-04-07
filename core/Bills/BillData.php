<?php

namespace ManiaControl\Bills;

use ManiaControl\Players\Player;

/**
 * ManiaControl BillData Structure
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2015 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class BillData {
	/*
	 * Public properties
	 */
	public $function      = null;
	public $pay           = false;
	public $player        = null;
	public $receiverLogin = null;
	public $amount        = 0;
	public $creationTime  = -1;
	public $message       = "";
	public $class         = "";

	/**
	 * Construct new Bill Data Model
	 *
	 * @api
	 * @param string        $class
	 * @param callable      $function
	 * @param Player|string $player
	 * @param int           $amount
	 * @param bool          $pay
	 * @param string        $receiverLogin
	 * @param string        $message
	 */
	public function __construct($class, callable $function, $player, $amount, $pay = false, $receiverLogin = null, $message = '') {
		$this->class         = $class;
		$this->function      = $function;
		$this->player        = $player;
		$this->amount        = $amount;
		$this->pay           = $pay;
		$this->receiverLogin = $receiverLogin;
		$this->message       = $message;
		$this->creationTime  = time();
	}
}
