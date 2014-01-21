<?php

namespace ManiaControl\Server;

use FML\Controls\Quads\Quad_Icons128x32_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

/**
 * Class offering various commands related to the dedicated server
 *
 * @author steeffeen & kremsy
 */
class ServerCommands implements CallbackListener, CommandListener, ManialinkPageAnswerListener {
	/**
	 * Constants
	 */
	const ACTION_SET_PAUSE   = 'ServerCommands.SetPause';
	const ACTION_CANCEL_VOTE = 'ServerCommands.CancelVote';
	const CB_VOTE_CANCELED   = 'ServerCommands.VoteCanceled';

	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $serverShutdownTime = -1;
	private $serverShutdownEmpty = false;

	/**
	 * Create a new server commands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_5_SECOND, $this, 'each5Seconds');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');

		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('setpwd', $this, 'command_SetPwd', true);
		$this->maniaControl->commandManager->registerCommandListener('setservername', $this, 'command_SetServerName', true);
		$this->maniaControl->commandManager->registerCommandListener('setmaxplayers', $this, 'command_SetMaxPlayers', true);
		$this->maniaControl->commandManager->registerCommandListener('setmaxspectators', $this, 'command_SetMaxSpectators', true);
		$this->maniaControl->commandManager->registerCommandListener('setspecpwd', $this, 'command_SetSpecPwd', true);
		$this->maniaControl->commandManager->registerCommandListener('shutdownserver', $this, 'command_ShutdownServer', true);
		$this->maniaControl->commandManager->registerCommandListener('systeminfo', $this, 'command_SystemInfo', true);

		$this->maniaControl->commandManager->registerCommandListener('cancel', $this, 'command_CancelVote', true);
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SET_PAUSE, $this, 'setPause');
	}

	/**
	 * Set Menu items on init
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		//Check if Pause exists in current gamemode
		$scriptInfos = $this->maniaControl->client->getModeScriptInfo();

		$pauseExists = false;
		foreach($scriptInfos->commandDescs as $param) {
			if($param->name == "Command_ForceWarmUp") {
				$pauseExists = true;
				break;
			}
		}

		// Set Pause
		if($pauseExists) {
			$itemQuad = new Quad_Icons128x32_1();
			$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ManiaLinkSwitch);
			$itemQuad->setAction(self::ACTION_SET_PAUSE);
			$this->maniaControl->actionsMenu->addAdminMenuItem($itemQuad, 1, 'Pauses the current game');
		}

		// Action cancel Vote
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CANCEL_VOTE, $this, 'command_cancelVote');
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowRed);
		$itemQuad->setAction(self::ACTION_CANCEL_VOTE);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, false, 6, 'Cancel Vote');
	}

	/**
	 * Handle //cancelvote command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_CancelVote(array $chatCallback, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$this->maniaControl->client->cancelVote();

		$this->maniaControl->chat->sendInformation('$<' . $player->nickname . '$> canceled the Vote!');

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_VOTE_CANCELED, array(self::CB_VOTE_CANCELED, $player));
	}

	/**
	 * Breaks the current game
	 *
	 * @param array $callback
	 */
	public function setPause(array $callback) {
		$success = $this->maniaControl->client->sendModeScriptCommands(array('Command_ForceWarmUp' => True));
		if(!$success) {
			$this->maniaControl->chat->sendError("Error while setting the pause");
		}
	}

	/**
	 * Check stuff each 5 seconds
	 *
	 * @param array $callback
	 * @return bool
	 */
	public function each5Seconds(array $callback) {
		// Empty shutdown
		if($this->serverShutdownEmpty) {
			$players = $this->maniaControl->playerManager->getPlayers();
			if(count($players) <= 0) {
				$this->shutdownServer('empty');
			}
		}

		// Delayed shutdown
		if($this->serverShutdownTime > 0) {
			if(time() >= $this->serverShutdownTime) {
				$this->shutdownServer('delayed');
			}
		}
	}

	/**
	 * Handle //systeminfo command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_SystemInfo(array $chat, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$systemInfo = $this->maniaControl->server->getSystemInfo();
		$message    = 'SystemInfo: ip=' . $systemInfo['PublishedIp'] . ', port=' . $systemInfo['Port'] . ', p2pPort=' . $systemInfo['P2PPort'] . ', title=' . $systemInfo['TitleId'] . ', login=' . $systemInfo['ServerLogin'] . '.';
		$this->maniaControl->chat->sendInformation($message, $player->login);
	}

	/**
	 * Handle //shutdownserver command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_ShutdownServer(array $chat, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		// Check for delayed shutdown
		$params = explode(' ', $chat[1][2]);
		if(count($params) >= 2) {
			$param = $params[1];
			if($param == 'empty') {
				$this->serverShutdownEmpty = !$this->serverShutdownEmpty;
				if($this->serverShutdownEmpty) {
					$this->maniaControl->chat->sendInformation("The server will shutdown as soon as it's empty!", $player->login);
					return;
				}
				$this->maniaControl->chat->sendInformation("Empty-shutdown cancelled!", $player->login);
				return;
			}
			$delay = (int)$param;
			if($delay <= 0) {
				// Cancel shutdown
				$this->serverShutdownTime = -1;
				$this->maniaControl->chat->sendInformation("Delayed shutdown cancelled!", $player->login);
				return;
			}
			// Trigger delayed shutdown
			$this->serverShutdownTime = time() + $delay * 60.;
			$this->maniaControl->chat->sendInformation("The server will shut down in {$delay} minutes!", $player->login);
			return;
		}
		$this->shutdownServer($player->login);
	}

	/**
	 * Handle //setservername command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_SetServerName(array $chat, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$params = explode(' ', $chat[1][2], 2);
		if(count($params) < 2) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setservername ManiaPlanet Server', $player->login);
			return;
		}
		$serverName = $params[1];
		try {
			$this->maniaControl->client->setServerName($serverName);
		} catch(Exception $e) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess("Server name changed to: '{$serverName}'!", $player->login);
	}

	/**
	 * Handle //setpwd command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_SetPwd(array $chatCallback, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$messageParts   = explode(' ', $chatCallback[1][2], 2);
		$password       = '';
		$successMessage = 'Password removed!';
		if(isset($messageParts[1])) {
			$password       = $messageParts[1];
			$successMessage = "Password changed to: '{$password}'!";
		}
		try {
			$this->maniaControl->client->setServerPassword($password);
		} catch(Exception $e) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess($successMessage, $player->login);
	}

	/**
	 * Handle //setspecpwd command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_SetSpecPwd(array $chatCallback, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$messageParts   = explode(' ', $chatCallback[1][2], 2);
		$password       = '';
		$successMessage = 'Spectator password removed!';
		if(isset($messageParts[1])) {
			$password       = $messageParts[1];
			$successMessage = "Spectator password changed to: '{$password}'!";
		}
		try {
			$this->maniaControl->client->setServerPasswordForSpectator($password);
		} catch(Exception $e) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess($successMessage, $player->login);
	}

	/**
	 * Handle //setmaxplayers command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_SetMaxPlayers(array $chatCallback, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$messageParts = explode(' ', $chatCallback[1][2], 2);
		if(!isset($messageParts[1])) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxplayers 16', $player->login);
			return;
		}
		$amount = $messageParts[1];
		if(!is_numeric($amount)) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxplayers 16', $player->login);
			return;
		}
		$amount = (int)$amount;
		if($amount < 0) {
			$amount = 0;
		}

		try {
			$this->maniaControl->client->setMaxPlayers($amount);
		} catch(Exception $e) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
			return;
		}

		$this->maniaControl->chat->sendSuccess("Changed max players to: {$amount}", $player->login);
	}

	/**
	 * Handle //setmaxspectators command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_SetMaxSpectators(array $chatCallback, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$messageParts = explode(' ', $chatCallback[1][2], 2);
		if(!isset($messageParts[1])) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxspectators 16', $player->login);
			return;
		}
		$amount = $messageParts[1];
		if(!is_numeric($amount)) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxspectators 16', $player->login);
			return;
		}
		$amount = (int)$amount;
		if($amount < 0) {
			$amount = 0;
		}

		try {
			$this->maniaControl->client->setMaxSpectators($amount);
		} catch(Exception $e) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess("Changed max spectators to: {$amount}", $player->login);
	}

	/**
	 * Perform server shutdown
	 *
	 * @param string $login
	 * @return bool
	 */
	private function shutdownServer($login = '#') {
		try {
			$this->maniaControl->client->stopServer();
		} catch(Exception $e) {
			trigger_error("Server shutdown command from '{login}' failed. " . $e->getMessage());
			return false;
		}
		$this->maniaControl->quit("Server shutdown requested by '{$login}'");
		return true;
	}
}
