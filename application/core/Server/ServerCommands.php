<?php

namespace ManiaControl\Server;

use ManiaControl\ManiaControl;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Players\Player;

/**
 * Class offering various commands related to the dedicated server
 *
 * @author steeffeen & kremsy
 */
class ServerCommands implements CallbackListener, CommandListener {
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
		
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_5_SECOND, $this, 'each5Seconds');
		
		$this->maniaControl->commandManager->registerCommandListener('/kick', $this, 'command_Kick');
		$this->maniaControl->commandManager->registerCommandListener('/setpwd', $this, 'command_SetPwd');
		$this->maniaControl->commandManager->registerCommandListener('/setservername', $this, 'command_SetServerName');
		$this->maniaControl->commandManager->registerCommandListener('/setmaxplayers', $this, 'command_SetMaxPlayers');
		$this->maniaControl->commandManager->registerCommandListener('/setmaxspectators', $this, 'command_SetMaxSpectators');
		$this->maniaControl->commandManager->registerCommandListener('/setspecpwd', $this, 'command_SetSpecPwd');
		$this->maniaControl->commandManager->registerCommandListener('/shutdown', $this, 'command_Shutdown');
		$this->maniaControl->commandManager->registerCommandListener('/shutdownserver', $this, 'command_ShutdownServer');
		$this->maniaControl->commandManager->registerCommandListener('/systeminfo', $this, 'command_SystemInfo');
	}

	/**
	 * Check stuff each 5 seconds
	 *
	 * @param array $callback        	
	 * @return bool
	 */
	public function each5Seconds(array $callback) {
		// Empty shutdown
		if ($this->serverShutdownEmpty) {
			$players = $this->maniaControl->server->getPlayers();
			if (count($players) <= 0) {
				return $this->shutdownServer('empty');
			}
		}
		
		// Delayed shutdown
		if ($this->serverShutdownTime > 0) {
			if (time() >= $this->serverShutdownTime) {
				return $this->shutdownServer('delayed');
			}
		}
	}

	/**
	 * Handle //systeminfo command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	public function command_SystemInfo(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$systemInfo = $this->maniaControl->server->getSystemInfo();
		$message = 'SystemInfo: ip=' . $systemInfo['PublishedIp'] . ', port=' . $systemInfo['Port'] . ', p2pPort=' .
				 $systemInfo['P2PPort'] . ', title=' . $systemInfo['TitleId'] . ', login=' . $systemInfo['ServerLogin'] . '.';
		return $this->maniaControl->chat->sendInformation($message, $player->login);
	}

	/**
	 * Handle //shutdown command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	public function command_Shutdown(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		return $this->maniaControl->quit("ManiaControl shutdown requested by '{$player->login}'");
	}

	/**
	 * Handle //shutdownserver command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	public function command_ShutdownServer(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		// Check for delayed shutdown
		$params = explode(' ', $chat[1][2]);
		if (count($params) >= 2) {
			$param = $params[1];
			if ($param == 'empty') {
				$this->serverShutdownEmpty = !$this->serverShutdownEmpty;
				if ($this->serverShutdownEmpty) {
					$this->maniaControl->chat->sendInformation("The server will shutdown as soon as it's empty!", $player->login);
					return true;
				}
				$this->maniaControl->chat->sendInformation("Empty-shutdown cancelled!", $player->login);
				return true;
			}
			$delay = (int) $param;
			if ($delay <= 0) {
				// Cancel shutdown
				$this->serverShutdownTime = -1;
				$this->maniaControl->chat->sendInformation("Delayed shutdown cancelled!", $player->login);
				return true;
			}
			// Trigger delayed shutdown
			$this->serverShutdownTime = time() + $delay * 60.;
			$this->maniaControl->chat->sendInformation("The server will shut down in {$delay} minutes!", $player->login);
			return true;
		}
		return $this->shutdownServer($player->login);
	}

	/**
	 * Handle //kick command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	public function command_Kick(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$params = explode(' ', $chat[1][2], 3);
		if (!isset($params[1])) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //kick login', $player->login);
			return false;
		}
		$target = $params[1];
		$target = $this->maniaControl->playerManager->getPlayer($target);
		if (!$target) {
			$this->maniaControl->chat->sendError("Invalid player login.", $player->login);
			return false;
		}
		$message = '';
		if (isset($params[2])) {
			$message = $params[2];
		}
		return $this->maniaControl->client->query('Kick', $target->login, $message);
	}

	/**
	 * Handle //setservername command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	public function command_SetServerName(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$params = explode(' ', $chat[1][2], 2);
		if (count($params) < 2) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setservername ManiaPlanet Server', $player->login);
			return false;
		}
		$serverName = $params[1];
		if (!$this->maniaControl->client->query('SetServerName', $serverName)) {
			$this->maniaControl->chat->sendError('Error occured: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return false;
		}
		$this->maniaControl->chat->sendSuccess("Server name changed to: '{$serverName}'!", $player->login);
		return true;
	}

	/**
	 * Handle //setpwd command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 * @return bool
	 */
	public function command_SetPwd(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$messageParts = explode(' ', $chatCallback[1][2], 2);
		$password = '';
		$successMessage = 'Password removed!';
		if (isset($messageParts[1])) {
			$password = $messageParts[1];
			$successMessage = "Password changed to: '{$password}'!";
		}
		$success = $this->maniaControl->client->query('SetServerPassword', $password);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occured: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return false;
		}
		$this->maniaControl->chat->sendSuccess($successMessage, $player->login);
		return true;
	}

	/**
	 * Handle //setspecpwd command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 * @return bool
	 */
	public function command_SetSpecPwd(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$messageParts = explode(' ', $chatCallback[1][2], 2);
		$password = '';
		$successMessage = 'Spectator password removed!';
		if (isset($messageParts[1])) {
			$password = $messageParts[1];
			$successMessage = "Spectator password changed to: '{$password}'!";
		}
		$success = $this->maniaControl->client->query('SetServerPasswordForSpectator', $password);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occured: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return false;
		}
		$this->maniaControl->chat->sendSuccess($successMessage, $player->login);
		return true;
	}

	/**
	 * Handle //setmaxplayers command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 * @return bool
	 */
	public function command_SetMaxPlayers(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$messageParts = explode(' ', $chatCallback[1][2], 2);
		if (!isset($messageParts[1])) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxplayers 16', $player->login);
			return false;
		}
		$amount = $messageParts[1];
		if (!is_numeric($amount)) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxplayers 16', $player->login);
			return false;
		}
		$amount = (int) $amount;
		if ($amount < 0) {
			$amount = 0;
		}
		$success = $this->maniaControl->client->query('SetMaxPlayers', $amount);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occured: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return false;
		}
		$this->maniaControl->chat->sendSuccess("Changed max players to: {$amount}", $player->login);
		return true;
	}

	/**
	 * Handle //setmaxspectators command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 * @return bool
	 */
	public function command_SetMaxSpectators(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$messageParts = explode(' ', $chatCallback[1][2], 2);
		if (!isset($messageParts[1])) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxspectators 16', $player->login);
			return false;
		}
		$amount = $messageParts[1];
		if (!is_numeric($amount)) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxspectators 16', $player->login);
			return false;
		}
		$amount = (int) $amount;
		if ($amount < 0) {
			$amount = 0;
		}
		$success = $this->maniaControl->client->query('SetMaxSpectators', $amount);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occured: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return false;
		}
		$this->maniaControl->chat->sendSuccess("Changed max spectators to: {$amount}", $player->login);
		return true;
	}

	/**
	 * Perform server shutdown
	 *
	 * @param string $login        	
	 * @return bool
	 */
	private function shutdownServer($login = '#') {
		if (!$this->maniaControl->client->query('StopServer')) {
			trigger_error("Server shutdown command from '{login}' failed. " . $this->maniaControl->getClientErrorText());
			return false;
		}
		$this->maniaControl->quit("Server shutdown requested by '{$login}'");
		return true;
	}
}

?>
