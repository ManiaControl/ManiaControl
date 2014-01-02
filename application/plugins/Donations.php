<?php
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\Plugin;

/**
 * Donation plugin
 *
 * @author steeffeen
 */
class DonationPlugin implements CallbackListener, CommandListener, Plugin {
	/**
	 * Constants
	 */
	const ID                              = 3;
	const VERSION                         = 0.1;
	const SETTING_ANNOUNCE_SERVERDONATION = 'Enable Server-Donation Announcements';
	const STAT_PLAYER_DONATIONS           = 'donatedPlanets';

	/**
	 * Private properties
	 */
	/** @var maniaControl $maniaControl */
	private $maniaControl = null;
	private $openBills = array();

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('donate', $this, 'command_Donate');
		$this->maniaControl->commandManager->registerCommandListener('pay', $this, 'command_Pay', true);
		$this->maniaControl->commandManager->registerCommandListener('getplanets', $this, 'command_GetPlanets', true);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_BILLUPDATED, $this, 'handleBillUpdated');

		// Define player stats
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_PLAYER_DONATIONS);
		return true;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
		$this->maniaControl->commandManager->unregisterCommandListener($this);
		unset($this->maniaControl);
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return 'Donations Plugin';
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return 'steeffeen';
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return 'Plugin offering commands like /donate, /pay and /getplanets and a donation widget.';
	}

	/**
	 * Handle /donate command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 * @return bool
	 */
	public function command_Donate(array $chatCallback, Player $player) {
		$text   = $chatCallback[1][2];
		$params = explode(' ', $text);
		if(count($params) < 2) {
			$this->sendDonateUsageExample($player);
			return false;
		}
		$amount = (int)$params[1];
		if(!$amount || $amount <= 0) {
			$this->sendDonateUsageExample($player);
			return false;
		}
		if(count($params) >= 3) {
			$receiver       = $params[2];
			$receiverPlayer = $this->maniaControl->playerManager->getPlayer($receiver);
			$receiverName   = ($receiverPlayer ? $receiverPlayer['NickName'] : $receiver);
		} else {
			$receiver     = '';
			$receiverName = $this->maniaControl->server->getName();
		}
		$message = 'Donate ' . $amount . ' Planets to $<' . $receiverName . '$>?';
		if(!$this->maniaControl->client->query('SendBill', $player->login, $amount, $message, $receiver)) {
			trigger_error("Couldn't create donation of {$amount} planets from '{$player->login}' for '{$receiver}'. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("Creating donation failed.", $player->login);
			return false;
		}
		$bill                   = $this->maniaControl->client->getResponse();
		$this->openBills[$bill] = array(true, $player->login, $receiver, $amount, time());
		return true;
	}

	/**
	 * Handle //pay command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 * @return bool
	 */
	public function command_Pay(array $chatCallback, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$text   = $chatCallback[1][2];
		$params = explode(' ', $text);
		if(count($params) < 2) {
			$this->sendPayUsageExample($player);
			return false;
		}
		$amount = (int)$params[1];
		if(!$amount || $amount <= 0) {
			$this->sendPayUsageExample($player);
			return false;
		}
		if(count($params) >= 3) {
			$receiver = $params[2];
		} else {
			$receiver = $player->login;
		}
		$message = 'Payout from $<' . $this->maniaControl->server->getName() . '$>.';
		if(!$this->maniaControl->client->query('Pay', $receiver, $amount, $message)) {
			trigger_error("Couldn't create payout of {$amount} planets by '{$player->login}' for '{$receiver}'. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("Creating payout failed.", $player->login);
			return false;
		}
		$bill                   = $this->maniaControl->client->getResponse();
		$this->openBills[$bill] = array(false, $player->login, $receiver, $amount, time());
		return true;
	}

	/**
	 * Handle //getplanets command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 * @return bool
	 */
	public function command_GetPlanets(array $chatCallback, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		if(!$this->maniaControl->client->query('GetServerPlanets')) {
			trigger_error("Couldn't retrieve server planets. " . $this->maniaControl->getClientErrorText());
			return false;
		}
		$planets = $this->maniaControl->client->getResponse();
		$message = "This Server has {$planets} Planets!";
		return $this->maniaControl->chat->sendInformation($message, $player->login);
	}

	/**
	 * Handle bill updated callback
	 *
	 * @param array $callback
	 * @return bool
	 */
	public function handleBillUpdated(array $callback) {
		$billId = $callback[1][0];
		if(!array_key_exists($billId, $this->openBills)) {
			return false;
		}
		$billData = $this->openBills[$billId];
		$login    = $billData[1];
		$receiver = $billData[2];
		switch($callback[1][1]) {
			case 4:
			{
				// Payed
				$donation = $billData[0];
				$amount   = $billData[3];
				if($donation) {
					// Donation
					if(strlen($receiver) > 0) {
						// To player
						$message = "Successfully donated {$amount} to '{$receiver}'!";
						$this->maniaControl->chat->sendSuccess($message, $login);
					} else {
						// To server
						if($this->maniaControl->settingManager->getSetting($this, self::SETTING_ANNOUNCE_SERVERDONATION, true)) {
							$message = '$<' . ($player ? $player->nickname : $login) . '$> donated ' . $amount . ' Planets! Thanks.';
						} else {
							$message = 'Donation successful! Thanks.';
						}
						$this->maniaControl->chat->sendSuccess($message, $login);
						$player = $this->maniaControl->playerManager->getPlayer($login);
						$this->maniaControl->statisticManager->insertStat(self::STAT_PLAYER_DONATIONS, $player, $this->maniaControl->server->getLogin(), $amount);
					}
				} else {
					// Payout
					$message = "Successfully payed out {$amount} to '{$receiver}'!";
					$this->maniaControl->chat->sendSuccess($message, $login);
				}
				unset($this->openBills[$billId]);
				break;
			}
			case 5:
			{
				// Refused
				$message = 'Transaction cancelled.';
				$this->maniaControl->chat->sendError($message, $login);
				unset($this->openBills[$billId]);
				break;
			}
			case 6:
			{
				// Error
				$this->maniaControl->chat->sendError($callback[1][2], $login);
				unset($this->openBills[$billId]);
				break;
			}
		}
		return true;
	}

	/**
	 * Send an usage example for /donate to the player
	 *
	 * @param Player $player
	 * @return boolean
	 */
	private function sendDonateUsageExample(Player $player) {
		$message = "Usage Example: '/donate 100'";
		return $this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Send an usage example for /pay to the player
	 *
	 * @param Player $player
	 * @return boolean
	 */
	private function sendPayUsageExample(Player $player) {
		$message = "Usage Example: '/pay 100 login'";
		return $this->maniaControl->chat->sendChat($message, $player->login);
	}
}
