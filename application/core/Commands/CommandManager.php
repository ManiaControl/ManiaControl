<?php

namespace ManiaControl\Commands;

require_once __DIR__ . '/CommandListener.php';

use ManiaControl\ManiaControl;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Players\Player;

/**
 * Class for handling chat commands
 *
 * @author steeffeen & kremsy
 */
// TODO: settings for command auth levels
class CommandManager implements CallbackListener, CommandListener {
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $commandListeners = array();
	private $serverShutdownTime = -1;
	private $serverShutdownEmpty = false;

	/**
	 * Construct commands manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_5_SECOND, $this, 'each5Seconds');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERCHAT, $this, 'handleChatCallback');
		
		// Register basic commands
		$commands = array('version', 'shutdown', 'shutdownserver', 'systeminfo', 'setservername', 'kick');
		foreach ($commands as $command) {
			$this->registerCommandListener($command, $this, 'command_' . $command);
		}
	}

	/**
	 * Register a command listener
	 *
	 * @param string $commandName        	
	 * @param CommandListener $listener        	
	 * @param string $method        	
	 * @return bool
	 */
	public function registerCommandListener($commandName, CommandListener $listener, $method) {
		$command = strtolower($commandName);
		if (!method_exists($listener, $method)) {
			trigger_error("Given listener can't handle command '{$command}' (no method '{$method}')!");
			return false;
		}
		if (!array_key_exists($command, $this->commandListeners) || !is_array($this->commandListeners[$command])) {
			// Init listeners array
			$this->commandListeners[$command] = array();
		}
		// Register command listener
		array_push($this->commandListeners[$command], array($listener, $method));
		return true;
	}

	/**
	 * Handle chat callback
	 *
	 * @param array $callback        	
	 * @return bool
	 */
	public function handleChatCallback(array $callback) {
		// Check for command
		if (!$callback[1][3]) {
			return false;
		}
		// Check for valid player
		$player = $this->maniaControl->playerManager->getPlayer($callback[1][1]);
		if (!$player) {
			return false;
		}
		// Handle command
		$command = explode(" ", substr($callback[1][2], 1));
		$command = strtolower($command[0]);
		if (!array_key_exists($command, $this->commandListeners) || !is_array($this->commandListeners[$command])) {
			// No command listener registered
			return true;
		}
		// Inform command listeners
		foreach ($this->commandListeners[$command] as $listener) {
			call_user_func(array($listener[0], $listener[1]), $callback, $player);
		}
		return true;
	}

	/**
	 * Send ManiaControl version
	 *
	 * @param array $chat        	
	 * @return bool
	 */
	private function command_version(array $chat) {
		$login = $chat[1][1];
		$message = 'This server is using ManiaControl v' . ManiaControl::VERSION . '!';
		return $this->maniaControl->chat->sendInformation($message, $login);
	}

	/**
	 * Handle systeminfo command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_systeminfo(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$systemInfo = $this->maniaControl->server->getSystemInfo();
		$message = 'SystemInfo: ip=' . $systemInfo['PublishedIp'] . ', port=' . $systemInfo['Port'] . ', p2pPort=' .
				 $systemInfo['P2PPort'] . ', title=' . $systemInfo['TitleId'] . ', login=' . $systemInfo['ServerLogin'] . ', ';
		return $this->maniaControl->chat->sendInformation($message, $player->login);
	}

	/**
	 * Handle shutdown command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_shutdown(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		return $this->maniaControl->quit("ManiaControl shutdown requested by '{$player->login}'");
	}

	/**
	 * Handle server shutdown command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_shutdownserver(array $chat, Player $player) {
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
	 * Handle kick command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_kick(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$params = explode(' ', $chat[1][2], 3);
		if (count($params) < 2) {
			// TODO: show usage
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
	 * Handle setservername command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_setservername(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$params = explode(' ', $chat[1][2], 2);
		if (count($params) < 2) {
			// TODO: show usage
			return false;
		}
		$serverName = $params[1];
		if (!$this->maniaControl->client->query('SetServerName', $serverName)) {
			trigger_error("Couldn't set server name. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("Error setting server name!", $player->login);
			return false;
		}
		$serverName = $this->maniaControl->server->getName();
		$this->maniaControl->chat->sendInformation("New Name: " . $serverName, $player->login);
		return true;
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
