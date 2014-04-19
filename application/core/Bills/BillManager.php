<?php

namespace ManiaControl\Bills;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Structures\Bill;

/**
 * ManiaControl Bill-Manager
 *
 * @author    kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class BillManager implements CallbackListener {
	/*
	 * Constants
	 */
	const DONATED_TO_SERVER       = 1;
	const DONATED_TO_RECEIVER     = 2;
	const PAYED_FROM_SERVER       = 3;
	const PLAYER_REFUSED_DONATION = 4;
	const ERROR_WHILE_TRANSACTION = 5;

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $openBills = array();

	/**
	 * Construct a new Bill Manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_BILLUPDATED, $this, 'handleBillUpdated');
	}

	/**
	 * send a Bill to a Player
	 *
	 * @param        $function
	 * @param Player $player
	 * @param        $amount
	 * @param        $message
	 * @param bool   $receiver
	 * @return bool
	 */
	public function sendBill($function, Player $player, $amount, $message, $receiver = false) {
		if (!is_callable($function)) {
			trigger_error("Function is not callable");
			return false;
		}

		if (!$receiver) {
			$bill = $this->maniaControl->client->sendBill($player->login, $amount, $message);
		} else {
			$bill = $this->maniaControl->client->sendBill($player->login, $amount, $message, $receiver);
		}

		$this->openBills[$bill] = new BillData($function, $player, $amount);
		return true;
	}

	/**
	 * Send Planets from the server to a Player
	 *
	 * @param $function
	 * @param $receiverLogin
	 * @param $amount
	 * @param $message
	 * @return bool
	 */
	public function sendPlanets($function, $receiverLogin, $amount, $message) {
		if (!is_callable($function)) {
			trigger_error("Function is not callable");
			return false;
		}

		$bill = $this->maniaControl->client->pay($receiverLogin, $amount, $message);

		$this->openBills[$bill] = new BillData($function, $receiverLogin, $amount, true);

		return true;
	}

	/**
	 * Handle bill updated callback
	 *
	 * @param array $callback
	 * @return bool
	 */
	public function handleBillUpdated(array $callback) {
		$billId = $callback[1][0];
		if (!array_key_exists($billId, $this->openBills)) {
			return;
		}
		$billData = $this->openBills[$billId];

		/** @var BillData $billData */
		switch($callback[1][1]) {
			case Bill::STATE_PAYED:
				if ($billData->pay) {
					call_user_func($billData->function, $billData, self::PAYED_FROM_SERVER);
				} else {
					if ($billData->receiverLogin) {
						call_user_func($billData->function, $billData, self::DONATED_TO_RECEIVER);
					} else {
						call_user_func($billData->function, $billData, self::DONATED_TO_SERVER);
					}
				}
				unset($this->openBills[$billId]);
				break;
			case Bill::STATE_REFUSED:
				call_user_func($billData->function, $billData, self::PLAYER_REFUSED_DONATION);
				unset($this->openBills[$billId]);
				break;
			case Bill::STATE_ERROR:
				call_user_func($billData->function, $callback[1][2], self::ERROR_WHILE_TRANSACTION);
				unset($this->openBills[$billId]);
				break;
		}
	}
}
