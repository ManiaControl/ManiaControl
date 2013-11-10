<?php

namespace ManiaControl;

/**
 * Class for handling chat commands
 *
 * @author steeffeen & kremsy
 */
// TODO: settings for command auth levels
class Commands {
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $commandHandlers = array();
	private $openBills = array();
	private $serverShutdownTime = -1;
	private $serverShutdownEmpty = false;

	/**
	 * Construct commands handler
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Register for callbacks
		$this->maniaControl->callbacks->registerCallbackHandler(Callbacks::CB_MC_5_SECOND, $this, 'each5Seconds');
		$this->maniaControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_BILLUPDATED, $this, 'handleBillUpdated');
		$this->maniaControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCHAT, $this, 'handleChatCallback');
		
		// Register basic commands
		$commands = array('help', 'version', 'shutdown', 'shutdownserver', 'systeminfo', 'setservername', 'getplanets', 'donate', 
			'pay', 'kick', 'nextmap', 'restartmap', 'addmap', 'removemap');
		foreach ($commands as $command) {
			$this->registerCommandHandler($command, $this, 'command_' . $command);
		}
	}

	/**
	 * Register a command handler
	 *
	 * @param string $commandName        	
	 * @param object $handler        	
	 * @param string $method        	
	 * @return bool
	 */
	public function registerCommandHandler($commandName, $handler, $method) {
		$command = strtolower($commandName);
		if (!is_object($handler) || !method_exists($handler, $method)) {
			trigger_error("Given handler can't handle command '{$command}' (no method '{$method}')!");
			return false;
		}
		if (!array_key_exists($command, $this->commandHandlers) || !is_array($this->commandHandlers[$command])) {
			// Init handlers array
			$this->commandHandlers[$command] = array();
		}
		// Register command handler
		array_push($this->commandHandlers[$command], array($handler, $method));
		return true;
	}

	/**
	 * Handle chat callback
	 *
	 * @param array $callback        	
	 * @return bool
	 */
	public function handleChatCallback(array $callback) {
		$chat = $callback[1];
		// Check for command
		if (!$chat[3]) {
			return false;
		}
		// Check for valid player
		$player = $this->maniaControl->playerHandler->getPlayer($login);
		if (!$player) {
			return false;
		}
		// Handle command
		$command = explode(" ", substr($chat[2], 1));
		$command = strtolower($command[0]);
		if (!array_key_exists($command, $this->commandHandlers) || !is_array($this->commandHandlers[$command])) {
			// No command handler registered
			return true;
		}
		// Inform command handlers
		foreach ($this->commandHandlers[$command] as $handler) {
			call_user_func(array($handler[0], $handler[1]), $callback, $player);
		}
		return true;
	}

	/**
	 * Handle bill updated callback
	 *
	 * @param array $callback        	
	 * @return bool
	 */
	public function handleBillUpdated(array $callback) {
		$bill = $callback[1];
		if (!array_key_exists($bill[0], $this->openBills)) {
			return false;
		}
		$login = $this->openBills[$bill[0]];
		switch ($bill[1]) {
			case 4:
				{
					// Payed
					$message = 'Success! Thanks.';
					$this->maniaControl->chat->sendSuccess($message, $login);
					unset($this->openBills[$bill[0]]);
					break;
				}
			case 5:
				{
					// Refused
					$message = 'Transaction cancelled.';
					$this->maniaControl->chat->sendError($message, $login);
					unset($this->openBills[$bill[0]]);
					break;
				}
			case 6:
				{
					// Error
					$this->maniaControl->chat->sendError($bill[2], $login);
					unset($this->openBills[$bill[0]]);
					break;
				}
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
	 * Send help list
	 *
	 * @param array $chat        	
	 * @return bool
	 */
	private function command_help(array $chat) {
		$login = $chat[1][1];
		// TODO: improve help command
		// TODO: enable help for specific commands
		$list = 'Available commands: ';
		$commands = array_keys($this->commandHandlers);
		$count = count($commands);
		for ($index = 0; $index < $count; $index++) {
			if (!$this->maniaControl->authentication->checkRight($login, $this->getRightsLevel($commands[$index], 'superadmin'))) {
				unset($commands[$index]);
			}
		}
		$count = count($commands);
		$index = 0;
		foreach ($commands as $command) {
			$list .= $command;
			if ($index < $count - 1) {
				$list .= ', ';
			}
			$index++;
		}
		return $this->maniaControl->chat->sendInformation($list, $login);
	}

	/**
	 * Handle getplanets command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_getplanets(array $chat, Player $player) {
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
			return false;
		}
		if (!$this->maniaControl->client->query('GetServerPlanets')) {
			trigger_error("Couldn't retrieve server planets. " . $this->maniaControl->getClientErrorText());
			return false;
		}
		$planets = $this->maniaControl->client->getResponse();
		$message = "This Server has {$planets} Planets!";
		return $this->maniaControl->chat->sendInformation($message, $player->login);
	}

	/**
	 * Handle donate command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_donate(array $chat, Player $player) {
		$params = explode(' ', $chat[1][2]);
		if (count($params) < 2) {
			// TODO: send usage information
			return false;
		}
		$amount = (int) $params[1];
		if (!$amount || $amount <= 0) {
			// TODO: send usage information
			return false;
		}
		if (count($params) >= 3) {
			$receiver = $params[2];
			$receiverPlayer = $this->maniaControl->playerHandler->getPlayer($receiver);
			$receiverName = ($receiverPlayer ? $receiverPlayer['NickName'] : $receiver);
		}
		else {
			$receiver = '';
			$receiverName = $this->maniaControl->server->getName();
		}
		$message = 'Donate ' . $amount . ' Planets to $<' . $receiverName . '$>?';
		if (!$this->maniaControl->client->query('SendBill', $pl, $amount, $message, $receiver)) {
			trigger_error(
					"Couldn't create donation of {$amount} planets from '{$player->login}' for '{$receiver}'. " .
							 $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("Creating donation failed.", $player->login);
			return false;
		}
		$bill = $this->maniaControl->client->getResponse();
		$this->openBills[$bill] = $player->login;
		return true;
	}

	/**
	 * Handle pay command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_pay(array $chat, Player $player) {
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
			return false;
		}
		$params = explode(' ', $chat[1][2]);
		if (count($params) < 2) {
			// TODO: send usage information
			return false;
		}
		$amount = (int) $params[1];
		if (!$amount || $amount <= 0) {
			// TODO: send usage information
			return false;
		}
		if (count($params) >= 3) {
			$receiver = $params[2];
		}
		else {
			$receiver = $player->login;
		}
		$message = 'Payout from $<' . $this->maniaControl->server->getName() . '$>.';
		if (!$this->maniaControl->client->query('Pay', $receiver, $amount, $message)) {
			trigger_error(
					"Couldn't create payout of {$amount} planets by '{$player->login}' for '{$receiver}'. " .
							 $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("Creating payout failed.", $player->login);
			return false;
		}
		$bill = $this->maniaControl->client->getResponse();
		$this->openBills[$bill] = $player->login;
		return true;
	}

	/**
	 * Handle systeminfo command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_systeminfo(array $chat, Player $player) {
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
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
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
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
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
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
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
			return false;
		}
		$params = explode(' ', $chat[1][2], 3);
		if (count($params) < 2) {
			// TODO: show usage
			return false;
		}
		$target = $params[1];
		$target = $this->maniaControl->playerHandler->getPlayer($target);
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
	 * Handle removemap command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_removemap(array $chat, Player $player) {
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
			return false;
		}
		// Get map name
		$map = $this->maniaControl->server->getMap();
		if (!$map) {
			$this->maniaControl->chat->sendError("Couldn't remove map.", $player->login);
			return false;
		}
		$mapName = $map['FileName'];
		// Remove map
		if (!$this->maniaControl->client->query('RemoveMap', $mapName)) {
			trigger_error("Couldn't remove current map. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("Couldn't remove map.", $player->login);
			return false;
		}
		$this->maniaControl->chat->sendSuccess('Map removed.', $player->login);
		return true;
	}

	/**
	 * Handle addmap command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_addmap(array $chat, Player $player) {
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
			return false;
		}
		$params = explode(' ', $chat[1][2], 2);
		if (count($params) < 2) {
			// TODO: show usage
			return false;
		}
		// Check if ManiaControl can even write to the maps dir
		if (!$this->maniaControl->client->query('GetMapsDirectory')) {
			trigger_error("Couldn't get map directory. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("ManiaControl couldn't retrieve the maps directory.", $player->login);
			return false;
		}
		$mapDir = $this->maniaControl->client->getResponse();
		if (!is_dir($mapDir)) {
			trigger_error("ManiaControl doesn't have have access to the maps directory in '{$mapDir}'.");
			$this->maniaControl->chat->sendError("ManiaControl doesn't have access to the maps directory.", $player->login);
			return false;
		}
		$downloadDirectory = $this->maniaControl->settingManager->getSetting($this, 'MapDownloadDirectory', 'mx');
		// Create download directory if necessary
		if (!is_dir($mapDir . $downloadDirectory) && !mkdir($mapDir . $downloadDirectory)) {
			trigger_error("ManiaControl doesn't have to rights to save maps in '{$mapDir}{$downloadDirectory}'.");
			$this->maniaControl->chat->sendError("ManiaControl doesn't have the rights to save maps.", $player->login);
			return false;
		}
		$mapDir .= $downloadDirectory . '/';
		// Download the map
		$mapId = $params[1];
		if (is_numeric($mapId)) {
			// Load from MX
			$serverInfo = $this->maniaControl->server->getSystemInfo();
			$title = strtolower(substr($serverInfo['TitleId'], 0, 2));
			// Check if map exists
			$url = "http://{$title}.mania-exchange.com/api/tracks/get_track_info/id/{$mapId}?format=json";
			$mapInfo = FileUtil::loadFile($url);
			if (!$mapInfo || strlen($mapInfo) <= 0) {
				// Invalid id
				$this->maniaControl->chat->sendError('Invalid MX-Id!', $player->login);
				return false;
			}
			$mapInfo = json_decode($mapInfo, true);
			$url = "http://{$title}.mania-exchange.com/tracks/download/{$mapId}";
			$file = FileUtil::loadFile($url);
			if (!$file) {
				// Download error
				$this->maniaControl->chat->sendError('Download failed!', $player->login);
				return false;
			}
			// Save map
			$fileName = $mapDir . $mapInfo['TrackID'] . '_' . $mapInfo['Name'] . '.Map.Gbx';
			if (!file_put_contents($fileName, $file)) {
				// Save error
				$this->maniaControl->chat->sendError('Saving map failed!', $player->login);
				return false;
			}
			// Check for valid map
			if (!$this->maniaControl->client->query('CheckMapForCurrentServerParams', $fileName)) {
				trigger_error("Couldn't check if map is valid. " . $this->maniaControl->getClientErrorText());
				$this->maniaControl->chat->sendError('Error checking map!', $player->login);
				return false;
			}
			$response = $this->maniaControl->client->getResponse();
			if (!$response) {
				// Inalid map type
				$this->maniaControl->chat->sendError("Invalid map type.", $player->login);
				return false;
			}
			// Add map to map list
			if (!$this->maniaControl->client->query('InsertMap', $fileName)) {
				$this->maniaControl->chat->sendError("Couldn't add map to match settings!", $player->login);
				return false;
			}
			$this->maniaControl->chat->sendSuccess('Map $<' . $mapInfo['Name'] . '$> added!');
			return true;
		}
		// TODO: add local map by filename
		// TODO: load map from direct url
	}

	/**
	 * Handle nextmap command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_nextmap(array $chat, Player $player) {
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
			return false;
		}
		return $this->maniaControl->client->query('NextMap');
	}

	/**
	 * Handle retartmap command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_restartmap(array $chat, Player $player) {
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
			return false;
		}
		return $this->maniaControl->client->query('RestartMap');
	}

	/**
	 * Handle setservername command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 * @return bool
	 */
	private function command_setservername(array $chat, Player $player) {
		if (!$this->maniaControl->authentication->checkRight($player, Authentication::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authentication->sendNotAllowed($player);
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
