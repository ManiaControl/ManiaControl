<?php

namespace ManiaControl\Bills;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Structures\Bill;

/**
 * ManiaControl Bill Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
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
	const CB_BILL_PAID           = 'Billmanager.BillPaid';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var BillData[] $openBills */
	private $openBills = array();

	/**
	 * Construct a new Bill Manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_BILLUPDATED, $this, 'handleBillUpdated');
	}

	/**
	 * Send a bill to a player
	 *
	 * @param callable $function
	 * @param Player   $player
	 * @param int      $amount
	 * @param string   $message
	 * @param string   $receiver
	 * @return bool
	 */
	public function sendBill(callable $function, Player $player, $amount, $message, $receiver = '') {
		$billId                   = $this->maniaControl->getClient()->sendBill($player->login, $amount, $message, $receiver);
		$this->openBills[$billId] = new BillData($function, $player, $amount);
		return true;
	}

	/**
	 * Send planets from the server to a player
	 *
	 * @param callable $function
	 * @param string   $receiverLogin
	 * @param int      $amount
	 * @param string   $message
	 * @return bool
	 */
	public function sendPlanets(callable $function, $receiverLogin, $amount, $message) {
		$billId                   = $this->maniaControl->getClient()->pay($receiverLogin, $amount, $message);
		$this->openBills[$billId] = new BillData($function, $receiverLogin, $amount, true);
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
		if (!isset($this->openBills[$billId])) {
			return;
		}
		$billData = $this->openBills[$billId];

		switch ($callback[1][1]) {
			case Bill::STATE_PAYED:
				if ($billData->pay) {
					call_user_func($billData->function, $billData, self::PAYED_FROM_SERVER);
					//Trigger a Callback for external Plugins
					$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_BILL_PAID, self::PAYED_FROM_SERVER, $billData);
				} else {
					if ($billData->receiverLogin) {
						call_user_func($billData->function, $billData, self::DONATED_TO_RECEIVER);
						//Trigger a Callback for external Plugins
						$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_BILL_PAID, self::DONATED_TO_RECEIVER, $billData);
					} else {
						call_user_func($billData->function, $billData, self::DONATED_TO_SERVER);
						//Trigger a Callback for external Plugins
						$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_BILL_PAID, self::DONATED_TO_SERVER, $billData);
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
