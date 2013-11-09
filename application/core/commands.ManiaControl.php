<?php

namespace ManiaControl;

/**
 * Class for handling chat commands
 *
 * @author steeffeen
 */
class Commands {

	/**
	 * Private properties
	 */
	private $mControl = null;

	private $config = null;

	private $commandHandlers = array();

	private $openBills = array();

	private $serverShutdownTime = -1;

	private $serverShutdownEmpty = false;

	/**
	 * Construct commands handler
	 */
	public function __construct($mControl) {
		$this->mControl = $mControl;
		
		// Load config
		$this->config = Tools::loadConfig('commands.ManiaControl.xml');
		
		// Register for callbacks
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_IC_5_SECOND, $this, 'each5Seconds');
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_BILLUPDATED, $this, 'handleBillUpdated');
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCHAT, $this, 'handleChatCallback');
		
		// Register basic commands
		$commands = array('help', 'version', 'shutdown', 'shutdownserver', 'networkstats', 'systeminfo', 'getservername', 
			'setservername', 'getplanets', 'donate', 'pay', 'kick', 'nextmap', 'restartmap', 'addmap', 'removemap', 'startwarmup', 
			'stopwarmup');
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
	 */
	public function registerCommandHandler($commandName, $handler, $method) {
		$command = strtolower($commandName);
		if (!is_object($handler) || !method_exists($handler, $method)) {
			trigger_error("Given handler can't handle command '" . $command . "' (no method '" . $method . "')!");
			return;
		}
		if (!array_key_exists($command, $this->commandHandlers) || !is_array($this->commandHandlers[$command])) {
			// Init handlers array
			$this->commandHandlers[$command] = array();
		}
		// Register command handler
		array_push($this->commandHandlers[$command], array($handler, $method));
	}

	/**
	 * Handle chat callback
	 */
	public function handleChatCallback($callback) {
		$chat = $callback[1];
		// Check for command
		if (!$chat[3]) return;
		// Check for valid player
		if ($chat[0] <= 0 || strlen($chat[1]) <= 0) return;
		// Handle command
		$command = explode(" ", substr($chat[2], 1));
		$command = strtolower($command[0]);
		if (!array_key_exists($command, $this->commandHandlers) || !is_array($this->commandHandlers[$command])) {
			// No command handler registered
			return;
		}
		// Inform command handlers
		foreach ($this->commandHandlers[$command] as $handler) {
			call_user_func(array($handler[0], $handler[1]), $callback);
		}
	}

	/**
	 * Handle bill updated callback
	 */
	public function handleBillUpdated($callback) {
		$bill = $callback[1];
		if (!array_key_exists($bill[0], $this->openBills)) return;
		$login = $this->openBills[$bill[0]];
		switch ($bill[1]) {
			case 4:
				{
					// Payed
					$message = 'Success! Thanks.';
					$this->iControl->chat->sendSuccess($message, $login);
					unset($this->openBills[$bill[0]]);
					break;
				}
			case 5:
				{
					// Refused
					$message = 'Transaction cancelled.';
					$this->iControl->chat->sendError($message, $login);
					unset($this->openBills[$bill[0]]);
					break;
				}
			case 6:
				{
					// Error
					$this->iControl->chat->sendError($bill[2], $login);
					unset($this->openBills[$bill[0]]);
					break;
				}
		}
	}

	/**
	 * Retrieve the needed rights level to perform the given command
	 *
	 * @param string $commandName        	
	 * @param string $defaultLevel        	
	 * @return string
	 */
	private function getRightsLevel($commandName, $defaultLevel) {
		$command_rights = $this->config->xpath('//' . strtolower($commandName) . '/..');
		if (empty($command_rights)) return $defaultLevel;
		$rights = $this->iControl->authentication->RIGHTS_LEVELS;
		$highest_level = null;
		foreach ($command_rights as $right) {
			$levelName = $right->getName();
			$levelInt = array_search($levelName, $rights);
			if ($levelInt !== false && ($highest_level === null || $highest_level < $levelInt)) {
				$highest_level = $levelInt;
			}
		}
		if ($highest_level === null || !array_key_exists($highest_level, $rights)) return $defaultLevel;
		return $rights[$highest_level];
	}

	/**
	 * Send ManiaControl version
	 */
	private function command_version($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('version', 'all'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		if (!$this->iControl->chat->sendInformation('This server is using ManiaControl v' . ManiaControl::VERSION . '!', $login)) {
			trigger_error("Couldn't send version to '" . $login . "'. " . $this->iControl->getClientErrorText());
		}
	}

	/**
	 * Send help list
	 */
	private function command_help($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('help', 'all'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		// TODO: improve help command
		// TODO: enable help for specific commands
		$list = 'Available commands: ';
		$commands = array_keys($this->commandHandlers);
		$count = count($commands);
		for ($index = 0; $index < $count; $index++) {
			if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel($commands[$index], 'superadmin'))) {
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
		if (!$this->iControl->chat->sendInformation($list, $login)) {
			trigger_error("Couldn't send help list to '" . $login . "'. " . $this->iControl->getClientErrorText());
		}
	}

	/**
	 * Handle getplanets command
	 */
	private function command_getplanets($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('getplanets', 'admin'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		if (!$this->iControl->client->query('GetServerPlanets')) {
			trigger_error("Couldn't retrieve server planets. " . $this->iControl->getClientErrorText());
		}
		else {
			$planets = $this->iControl->client->getResponse();
			if (!$this->iControl->chat->sendInformation('This Server has ' . $planets . ' Planets!', $login)) {
				trigger_error("Couldn't send server planets to '" . $login . "'. " . $this->iControl->getClientErrorText());
			}
		}
	}

	/**
	 * Handle donate command
	 */
	private function command_donate($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('donate', 'all'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		$params = explode(' ', $chat[1][2]);
		if (count($params) < 2) {
			// TODO: send usage information
			return;
		}
		$amount = (int) $params[1];
		if (!$amount || $amount <= 0) {
			// TODO: send usage information
			return;
		}
		if (count($params) >= 3) {
			$receiver = $params[2];
			$receiverPlayer = $this->iControl->database->getPlayer($receiver);
			$receiverName = ($receiverPlayer ? $receiverPlayer['NickName'] : $receiver);
		}
		else {
			$receiver = '';
			$receiverName = $this->iControl->server->getName();
		}
		$message = 'Donate ' . $amount . ' Planets to $<' . $receiverName . '$>?';
		if (!$this->iControl->client->query('SendBill', $login, $amount, $message, $receiver)) {
			trigger_error(
					"Couldn't create donation of " . $amount . " planets from '" . $login . "' for '" . $receiver . "'. " .
							 $this->iControl->getClientErrorText());
			$this->iControl->chat->sendError("Creating donation failed.", $login);
		}
		else {
			$bill = $this->iControl->client->getResponse();
			$this->openBills[$bill] = $login;
		}
	}

	/**
	 * Handle pay command
	 */
	private function command_pay($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('pay', 'superadmin'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		$params = explode(' ', $chat[1][2]);
		if (count($params) < 2) {
			// TODO: send usage information
			return;
		}
		$amount = (int) $params[1];
		if (!$amount || $amount <= 0) {
			// TODO: send usage information
			return;
		}
		if (count($params) >= 3) {
			$receiver = $params[2];
		}
		else {
			$receiver = $login;
		}
		$message = 'Payout from $<' . $this->iControl->server->getName() . '$>.';
		if (!$this->iControl->client->query('Pay', $receiver, $amount, $message)) {
			trigger_error(
					"Couldn't create payout of" . $amount . " planets by '" . $login . "' for '" . $receiver . "'. " .
							 $this->iControl->getClientErrorText());
			$this->iControl->chat->sendError("Creating payout failed.", $login);
		}
		else {
			$bill = $this->iControl->client->getResponse();
			$this->openBills[$bill] = $login;
		}
	}

	/**
	 * Handle networkstats command
	 */
	private function command_networkstats($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('networkstats', 'superadmin'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		$networkStats = $this->iControl->server->getNetworkStats();
		$message = 'NetworkStats: ' . 'uptime=' . $networkStats['Uptime'] . ', ' . 'nbConn=' . $networkStats['NbrConnection'] . ', ' .
				 'recvRate=' . $networkStats['RecvNetRate'] . ', ' . 'sendRate=' . $networkStats['SendNetRate'] . ', ' . 'recvTotal=' .
				 $networkStats['SendNetRate'] . ', ' . 'sentTotal=' . $networkStats['SendNetRate'];
		if (!$this->iControl->chat->sendInformation($message, $login)) {
			trigger_error("Couldn't send network stats to '" . $login . "'. " . $this->iControl->getClientErrorText());
		}
	}

	/**
	 * Handle systeminfo command
	 */
	private function command_systeminfo($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('systeminfo', 'superadmin'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		$systemInfo = $this->iControl->server->getSystemInfo();
		$message = 'SystemInfo: ' . 'ip=' . $systemInfo['PublishedIp'] . ', ' . 'port=' . $systemInfo['Port'] . ', ' . 'p2pPort=' .
				 $systemInfo['P2PPort'] . ', ' . 'title=' . $systemInfo['TitleId'] . ', ' . 'login=' . $systemInfo['ServerLogin'] . ', ';
		if (!$this->iControl->chat->sendInformation($message, $login)) {
			trigger_error("Couldn't send system info to '" . $login . "'. " . $this->iControl->getClientErrorText());
		}
	}

	/**
	 * Handle shutdown command
	 */
	private function command_shutdown($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('shutdown', 'superadmin'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		$this->iControl->quit("ManiaControl shutdown requested by '" . $login . "'");
	}

	/**
	 * Handle startwarmup command
	 */
	private function command_startwarmup($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('startwarmup', 'operator'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		if (!$this->iControl->client->query("SetWarmUp", true)) {
			trigger_error("Couldn't start warmup. " . $this->iControl->getClientErrorText());
			$player = $this->iControl->database->getPlayer($login);
			$this->iControl->chat->sendInformation('$<' . ($player ? $player['NickName'] : $login) . '$> started WarmUp!');
		}
	}

	/**
	 * Handle stopwarmup command
	 */
	private function command_stopwarmup($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('stopwarmup', 'operator'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		if (!$this->iControl->client->query("SetWarmUp", false)) {
			trigger_error("Couldn't stop warmup. " . $this->iControl->getClientErrorText());
		}
		else {
			$player = $this->iControl->database->getPlayer($login);
			$this->iControl->chat->sendInformation('$<' . ($player ? $player['NickName'] : $login) . '$> stopped WarmUp!');
		}
	}

	/**
	 * Handle server shutdown command
	 */
	private function command_shutdownserver($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('shutdownserver', 'superadmin'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		// Check for delayed shutdown
		$params = explode(' ', $chat[1][2]);
		if (count($params) >= 2) {
			$param = $params[1];
			if ($param == 'empty') {
				$this->serverShutdownEmpty = !$this->serverShutdownEmpty;
				if ($this->serverShutdownEmpty) {
					$this->iControl->chat->sendInformation("The server will shutdown as soon as it's empty!", $login);
				}
				else {
					$this->iControl->chat->sendInformation("Empty-shutdown cancelled!", $login);
				}
			}
			else {
				$delay = (int) $param;
				if ($delay <= 0) {
					// Cancel shutdown
					$this->serverShutdownTime = -1;
					$this->iControl->chat->sendInformation("Delayed shutdown cancelled!", $login);
				}
				else {
					// Trigger delayed shutdown
					$this->serverShutdownTime = time() + $delay * 60.;
					$this->iControl->chat->sendInformation("The server will shut down in " . $delay . " minutes!", $login);
				}
			}
		}
		else {
			$this->shutdownServer($login);
		}
	}

	/**
	 * Handle kick command
	 */
	private function command_kick($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('kick', 'operator'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		$params = explode(' ', $chat[1][2], 3);
		if (count($params) < 2) {
			// TODO: show usage
			return;
		}
		$target = $params[1];
		$players = $this->iControl->server->getPlayers();
		foreach ($players as $player) {
			if ($player['Login'] != $target) continue;
			// Kick player
			if (isset($params[2])) {
				$message = $params[2];
			}
			else {
				$message = "";
			}
			if (!$this->iControl->client->query('Kick', $target, $message)) {
				trigger_error("Couldn't kick player '" . $target . "'! " . $this->iControl->getClientErrorText());
			}
			return;
		}
		$this->iControl->chat->sendError("Invalid player login.", $login);
	}

	/**
	 * Handle removemap command
	 */
	private function command_removemap($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('kick', 'operator'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		// TODO: allow params
		// Get map name
		$map = $this->iControl->server->getMap();
		if (!$map) {
			$this->iControl->chat->sendError("Couldn't remove map.", $login);
		}
		else {
			$mapName = $map['FileName'];
			
			// Remove map
			if (!$this->iControl->client->query('RemoveMap', $mapName)) {
				trigger_error("Couldn't remove current map. " . $this->iControl->getClientErrorText());
			}
			else {
				$this->iControl->chat->sendSuccess('Map removed.', $login);
			}
		}
	}

	/**
	 * Handle addmap command
	 */
	private function command_addmap($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('addmap', 'operator'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		$params = explode(' ', $chat[1][2], 2);
		if (count($params) < 2) {
			// TODO: show usage
			return;
		}
		// Check if ManiaControl can even write to the maps dir
		if (!$this->iControl->client->query('GetMapsDirectory')) {
			trigger_error("Couldn't get map directory. " . $this->iControl->getClientErrorText());
			$this->iControl->chat->sendError("ManiaControl couldn't retrieve the maps directory.", $login);
			return;
		}
		else {
			$mapDir = $this->iControl->client->getResponse();
			if (!is_dir($mapDir)) {
				trigger_error("ManiaControl doesn't have have access to the maps directory in '" . $mapDir . "'.");
				$this->iControl->chat->sendError("ManiaControl doesn't have access to the maps directory.", $login);
				return;
			}
			$dlDir = (string) $this->iControl->config->maps_dir;
			// Create mx directory if necessary
			if (!is_dir($mapDir . $dlDir) && !mkdir($mapDir . $dlDir)) {
				trigger_error("ManiaControl doesn't have to rights to save maps in'" . $mapDir . $dlDir, "'.");
				$this->iControl->chat->sendError("ManiaControl doesn't have to rights to save maps.", $login);
				return;
			}
			$mapDir .= $dlDir . '/';
			// Download the map
			if (is_numeric($params[1])) {
				$serverInfo = $this->iControl->server->getSystemInfo();
				$title = strtolower(substr($serverInfo['TitleId'], 0, 2));
				// Check if map exists
				$url = 'http://' . $title . '.mania-exchange.com/api/tracks/get_track_info/id/' . $params[1] . '?format=json';
				$mapInfo = Tools::loadFile($url);
				if (!$mapInfo || strlen($mapInfo) <= 0) {
					// Invalid id
					$this->iControl->chat->sendError('Invalid MX-Id!', $login);
					return;
				}
				$mapInfo = json_decode($mapInfo, true);
				$url = 'http://' . $title . '.mania-exchange.com/tracks/download/' . $params[1];
				$file = Tools::loadFile($url);
				if (!$file) {
					// Download error
					$this->iControl->chat->sendError('Download failed!', $login);
					return;
				}
				// Save map
				$fileName = $mapDir . $mapInfo['TrackID'] . '_' . $mapInfo['Name'] . '.Map.Gbx';
				if (!file_put_contents($fileName, $file)) {
					// Save error
					$this->iControl->chat->sendError('Saving map failed!', $login);
					return;
				}
				// Check for valid map
				if (!$this->iControl->client->query('CheckMapForCurrentServerParams', $fileName)) {
					trigger_error("Couldn't check if map is valid. " . $this->iControl->getClientErrorText());
				}
				else {
					$response = $this->iControl->client->getResponse();
					if (!$response) {
						// Inalid map type
						$this->iControl->chat->sendError("Invalid map type.", $login);
						return;
					}
				}
				// Add map to map list
				if (!$this->iControl->client->query('InsertMap', $fileName)) {
					$this->iControl->chat->sendError("Couldn't add map to match settings!", $login);
					return;
				}
				$this->iControl->chat->sendSuccess('Map $<' . $mapInfo['Name'] . '$> successfully added!');
			}
			else {
				// TODO: check if map exists locally
				// TODO: load map from direct url
			}
		}
	}

	/**
	 * Handle nextmap command
	 */
	private function command_nextmap($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('nextmap', 'operator'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		if (!$this->iControl->client->query('NextMap')) {
			trigger_error("Couldn't skip map. " . $this->iControl->getClientErrorText());
		}
	}

	/**
	 * Handle retartmap command
	 */
	private function command_restartmap($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('restartmap', 'operator'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		if (!$this->iControl->client->query('RestartMap')) {
			trigger_error("Couldn't restart map. " . $this->iControl->getClientErrorText());
		}
	}

	/**
	 * Handle getservername command
	 */
	private function command_getservername($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('getservername', 'operator'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		$serverName = $this->iControl->server->getName();
		$this->iControl->chat->sendInformation("Server Name: " . $serverName, $login);
	}

	/**
	 * Handle setservername command
	 */
	private function command_setservername($chat) {
		$login = $chat[1][1];
		if (!$this->iControl->authentication->checkRight($login, $this->getRightsLevel('setservername', 'admin'))) {
			// Not allowed!
			$this->iControl->authentication->sendNotAllowed($login);
			return;
		}
		$params = explode(' ', $chat[1][2], 2);
		if (count($params) < 2) {
			// TODO: show usage
			return;
		}
		$serverName = $params[1];
		if (!$this->iControl->client->query('SetServerName', $serverName)) {
			trigger_error("Couldn't set server name. " . $this->iControl->getClientErrorText());
			$this->iControl->chat->sendError("Error!");
		}
		else {
			$serverName = $this->iControl->server->getName();
			$this->iControl->chat->sendInformation("New Name: " . $serverName);
		}
	}

	/**
	 * Check stuff each 5 seconds
	 */
	public function each5Seconds() {
		// Empty shutdown
		if ($this->serverShutdownEmpty) {
			$players = $this->iControl->server->getPlayers();
			if (count($players) <= 0) {
				$this->shutdownServer('empty');
			}
		}
		
		// Delayed shutdown
		if ($this->serverShutdownTime > 0) {
			if (time() >= $this->serverShutdownTime) {
				$this->shutdownServer('delayed');
			}
		}
	}

	/**
	 * Perform server shutdown
	 */
	private function shutdownServer($login = '#') {
		$this->iControl->client->resetError();
		if (!$this->iControl->client->query('StopServer') || $this->iControl->client->isError()) {
			trigger_error("Server shutdown command from '" . $login . "' failed. " . $this->iControl->getClientErrorText());
			return;
		}
		$this->iControl->quit("Server shutdown requested by '" . $login . "'");
	}
}

?>
