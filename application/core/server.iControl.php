<?php

namespace iControl;

/**
 * Class providing information and commands for the connected maniaplanet server
 *
 * @author steeffeen
 */
class Server {
	/**
	 * Constants
	 */
	const VALIDATIONREPLAYDIR = 'ValidationReplays/';
	const GHOSTREPLAYDIR = 'GhostReplays/';

	/**
	 * Public properties
	 */
	public $config = null;

	/**
	 * Private properties
	 */
	private $iControl = null;

	/**
	 * Construct server
	 */
	public function __construct($iControl) {
		$this->iControl = $iControl;
		
		// Load config
		$this->config = Tools::loadConfig('server.iControl.xml');
		$this->iControl->checkConfig($this->config, array('host', 'port', 'login', 'pass'), 'server');
		
		// Register for callbacks
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_IC_1_SECOND, $this, 'eachSecond');
	}

	/**
	 * Perform actions every second
	 */
	public function eachSecond() {
		// Delete cached information
		$this->players = null;
	}

	/**
	 * Fetch game directory of the server
	 *
	 * @return string
	 */
	public function getDataDirectory() {
		if (!$this->iControl->client->query('GameDataDirectory')) {
			trigger_error("Couldn't get data directory. " . $this->iControl->getClientErrorText());
			return null;
		}
		return $this->iControl->client->getResponse();
	}

	/**
	 * Checks if iControl has access to the given directory (server data directory if no param)
	 *
	 * @param string $directory        	
	 * @return bool
	 */
	public function checkAccess($directory = null) {
		if (!$directory) {
			$directory = $this->getDataDirectory();
		}
		return is_dir($directory) && is_writable($directory);
	}

	/**
	 * Fetch server login
	 */
	public function getLogin($client = null) {
		$systemInfo = $this->getSystemInfo(false, $client);
		if (!$systemInfo) return null;
		return $systemInfo['ServerLogin'];
	}

	/**
	 * Get detailed server info
	 */
	public function getInfo($detailed = false) {
		if ($detailed) {
			$login = $this->getLogin();
			if (!$this->iControl->client->query('GetDetailedPlayerInfo', $login)) {
				trigger_error("Couldn't fetch detailed server player info. " . $this->iControl->getClientErrorText());
				return null;
			}
		}
		else {
			if (!$this->iControl->client->query('GetMainServerPlayerInfo')) {
				trigger_error("Couldn't fetch server player info. " . $this->iControl->getClientErrorText());
				return null;
			}
		}
		return $this->iControl->client->getResponse();
	}

	/**
	 * Get server options
	 */
	public function getOptions() {
		if (!$this->iControl->client->query('GetServerOptions')) {
			trigger_error("Couldn't fetch server options. " . $this->iControl->getClientErrorText());
			return null;
		}
		return $this->iControl->client->getResponse();
	}

	/**
	 * Fetch server name
	 */
	public function getName() {
		if (!$this->iControl->client->query('GetServerName')) {
			trigger_error("Couldn't fetch server name. " . $this->iControl->getClientErrorText());
			return null;
		}
		return $this->iControl->client->getResponse();
	}

	/**
	 * Fetch server version
	 */
	public function getVersion($forceRefresh = false) {
		if (isset($this->iControl->client->version) && !$forceRefresh) return $this->iControl->client->version;
		if (!$this->iControl->client->query('GetVersion')) {
			trigger_error("Couldn't fetch server version. " . $this->iControl->getClientErrorText());
			return null;
		}
		else {
			$this->iControl->client->version = $this->iControl->client->getResponse();
			return $this->iControl->client->version;
		}
	}

	/**
	 * Fetch server system info
	 */
	public function getSystemInfo($forceRefresh = false, &$client = null) {
		if (!$this->iControl->client && !$client) return null;
		if (!$client) $client = $this->iControl->client;
		if (isset($client->systemInfo) && !$forceRefresh) return $client->systemInfo;
		if (!$client->query('GetSystemInfo')) {
			trigger_error("Couldn't fetch server system info. " . $this->iControl->getClientErrorText($client));
			return null;
		}
		else {
			$client->systemInfo = $client->getResponse();
			return $client->systemInfo;
		}
	}

	/**
	 * Fetch network status
	 */
	public function getNetworkStats($forceRefresh = false) {
		if (isset($this->iControl->client->networkStats) && !$forceRefresh) return $this->iControl->client->networkStats;
		if (!$this->iControl->client->query('GetNetworkStats')) {
			trigger_error("Couldn't fetch network stats. " . $this->iControl->getClientErrorText());
			return null;
		}
		else {
			$this->iControl->client->networkStats = $this->iControl->client->getResponse();
			return $this->iControl->client->networkStats;
		}
	}

	/**
	 * Fetch current game mode
	 *
	 * @param bool $stringValue        	
	 * @param int $parseValue        	
	 * @return int | string
	 */
	public function getGameMode($stringValue = false, $parseValue = null) {
		if (is_int($parseValue)) {
			$gameMode = $parseValue;
		}
		else {
			if (!$this->iControl->client->query('GetGameMode')) {
				trigger_error("Couldn't fetch current game mode. " . $this->iControl->getClientErrorText());
				return null;
			}
			$gameMode = $this->iControl->client->getResponse();
		}
		if ($stringValue) {
			switch ($gameMode) {
				case 0:
					{
						return 'Script';
					}
				case 1:
					{
						return 'Rounds';
					}
				case 2:
					{
						return 'TimeAttack';
					}
				case 3:
					{
						return 'Team';
					}
				case 4:
					{
						return 'Laps';
					}
				case 5:
					{
						return 'Cup';
					}
				case 6:
					{
						return 'Stunts';
					}
				default:
					{
						return 'Unknown';
					}
			}
		}
		return $gameMode;
	}

	/**
	 * Fetch player info
	 *
	 * @param string $login        	
	 * @return struct
	 */
	public function getPlayer($login, $detailed = false) {
		if (!$login) return null;
		$command = ($detailed ? 'GetDetailedPlayerInfo' : 'GetPlayerInfo');
		if (!$this->iControl->client->query($command, $login)) {
			trigger_error("Couldn't player info for '" . $login . "'. " . $this->iControl->getClientErrorText());
			return null;
		}
		return $this->iControl->client->getResponse();
	}

	/**
	 * Fetch all players
	 */
	public function getPlayers(&$client = null, &$purePlayers = null, &$pureSpectators = null) {
		if (!$this->iControl->client && !$client) return null;
		if (!$client) $client = $this->iControl->client;
		$fetchLength = 30;
		$offset = 0;
		$players = array();
		if (!is_array($purePlayers)) $purePlayers = array();
		if (!is_array($pureSpectators)) $pureSpectators = array();
		$tries = 0;
		while ($tries < 10) {
			if (!$client->query('GetPlayerList', $fetchLength, $offset)) {
				trigger_error("Couldn't get player list. " . $this->iControl->getClientErrorText($client));
				$tries++;
			}
			else {
				$chunk = $client->getResponse();
				$count = count($chunk);
				$serverLogin = $this->getLogin($client);
				for ($index = 0; $index < $count; $index++) {
					$login = $chunk[$index]['Login'];
					if ($login === $serverLogin) {
						// Ignore server
						unset($chunk[$index]);
					}
					else {
						if ($chunk[$index]['SpectatorStatus'] > 0) {
							// Pure spectator
							array_push($pureSpectators, $chunk[$index]);
						}
						else {
							// Pure player
							array_push($purePlayers, $chunk[$index]);
						}
					}
				}
				$players = array_merge($players, $chunk);
				$offset += $count;
				if ($count < $fetchLength) break;
			}
		}
		return $players;
	}

	/**
	 * Retrieve validation replay for given login
	 *
	 * @param string $login        	
	 * @return string
	 */
	public function getValidationReplay($login) {
		if (!$login) return null;
		if (!$this->iControl->client->query('GetValidationReplay', $login)) {
			trigger_error("Couldn't get validation replay of '" . $login . "'. " . $this->iControl->getClientErrorText());
			return null;
		}
		return $this->iControl->client->getResponse();
	}

	public function getGhostReplay($login) {
		$dataDir = $this->getDataDirectory();
		if (!$this->checkAccess($dataDir)) {
			return null;
		}
		
		// Build file name
		$map = $this->getMap();
		$gameMode = $this->getGameMode();
		$time = time();
		$fileName = 'Ghost.' . $login . '.' . $gameMode . '.' . $time . '.' . $map['UId'] . '.Replay.Gbx';
		
		// Save ghost replay
		if (!$this->iControl->client->query('SaveBestGhostsReplay', $login, self::GHOSTREPLAYDIR . $fileName)) {
			trigger_error("Couldn't save ghost replay. " . $this->iControl->getClientErrorText());
			return null;
		}
		
		// Load replay file
		$ghostReplay = file_get_contents($dataDir . 'Replays/' . self::GHOSTREPLAYDIR . $fileName);
		if (!$ghostReplay) {
			trigger_error("Couldn't retrieve saved ghost replay.");
			return null;
		}
		return $ghostReplay;
	}

	/**
	 * Fetch current map
	 */
	public function getMap() {
		if (!$this->iControl->client) return null;
		if (!$this->iControl->client->query('GetCurrentMapInfo')) {
			trigger_error("Couldn't fetch map info. " . $this->iControl->getClientErrorText());
			return null;
		}
		return $this->iControl->client->getResponse();
	}

	/**
	 * Waits for the server to have the given status
	 */
	public function waitForStatus($client, $statusCode = 4) {
		$client->query('GetStatus');
		$response = $client->getResponse();
		// Check if server reached given status
		if ($response['Code'] === 4) return true;
		// Server not yet in given status -> Wait for it...
		$waitBegin = time();
		$timeoutTags = $this->iControl->config->xpath('timeout');
		$maxWaitTime = (!empty($timeoutTags) ? (int) $timeoutTags[0] : 20);
		$lastStatus = $response['Name'];
		error_log("Waiting for server to reach status " . $statusCode . "...");
		error_log("Current Status: " . $lastStatus);
		while ($response['Code'] !== 4) {
			sleep(1);
			$client->query('GetStatus');
			$response = $client->getResponse();
			if ($lastStatus !== $response['Name']) {
				error_log("New Status: " . $response['Name']);
				$lastStatus = $response['Name'];
			}
			if (time() - $maxWaitTime > $waitBegin) {
				// It took too long to reach the status
				trigger_error(
						"Server couldn't reach status " . $statusCode . " after " . $maxWaitTime . " seconds! " .
								 $this->iControl->getClientErrorText());
				return false;
			}
		}
		return true;
	}
}

?>
