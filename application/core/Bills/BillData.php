<?php

namespace ManiaControl\Bills;

/**
 * ManiaControl BillData Structure
 *
 * @author kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class BillData {
	/*
	 * Public Properties
	 */
	public $function = null;
	public $pay = false;
	public $player = null;
	public $receiverLogin = false;
	public $amount = 0;
	public $creationTime = -1;

	/**
	 * Construct new BillData
	 * @param unknown $function
	 * @param unknown $player
	 * @param unknown $amount
	 * @param string $pay
	 * @param string $receiverLogin
	 */
	public function __construct($function, $player, $amount, $pay = false, $receiverLogin = false) {
		$this->function      = $function;
		$this->player        = $player;
		$this->amount        = $amount;
		$this->pay           = $pay;
		$this->receiverLogin = $receiverLogin;
		$this->creationTime  = time();
	}

} 