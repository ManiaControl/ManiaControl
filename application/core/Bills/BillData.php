<?php
/**
 * ManiaControl BillData Structure
 *
 * @author kremsy and steeffeen
 */
namespace ManiaControl\Bills;


class BillData {
	public $function = null;
	public $pay = false;
	public $player = null;
	public $receiverLogin = false;
	public $amount = 0;
	public $creationTime = -1;

	public function __construct($function, $player, $amount, $pay = false, $receiverLogin = false) {
		$this->function      = $function;
		$this->player        = $player;
		$this->amount        = $amount;
		$this->pay           = $pay;
		$this->receiverLogin = $receiverLogin;
		$this->creationTime  = time();
	}

} 