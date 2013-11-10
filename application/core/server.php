<?php

namespace ManiaControl;

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
	private $maniaConnect = null;

	/**
	 * Construct server
	 */
	public function __construct($maniaConnect) {
		$this->maniaConnect = $maniaConnect;
		
		// Load config
		$this->config = FileUtil::loadConfig('server.xml');
		
		// Register for callbacks
		$this->maniaConnect->callbacks->registerCallbackHandler(Callbacks::CB_MC_1_SECOND, $this, 'eachSecond');
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
		if (!$this->maniaConnect->client->query('GameDataDirectory')) {
			trigger_error("Couldn't get data directory. " . $this->maniaConnect->getClientErrorText());
			return null;
		}
		return $this->maniaConnect->client->getResponse();
	}

	/**
	 * Checks if ManiaControl has access to the given directory (server data directory if no param)
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
			if (!$this->maniaConnect->client->query('GetDetailedPlayerInfo', $login)) {
				trigger_error("Couldn't fetch detailed server player info. " . $this->maniaConnect->getClientErrorText());
				return null;
			}
		}
		else {
			if (!$this->maniaConnect->client->query('GetMainServerPlayerInfo')) {
				trigger_error("Couldn't fetch server player info. " . $this->maniaConnect->getClientErrorText());
				return null;
			}
		}
		return $this->maniaConnect->client->getResponse();
	}

	/**
	 * Get server options
	 */
	public function getOptions() {
		if (!$this->maniaConnect->client->query('GetServerOptions')) {
			trigger_error("Couldn't fetch server options. " . $this->maniaConnect->getClientErrorText());
			return null;
		}
		return $this->maniaConnect->client->getResponse();
	}

	/**
	 * Fetch server name
	 */
	public function getName() {
		if (!$this->maniaConnect->client->query('GetServerName')) {
			trigger_error("Couldn't fetch server name. " . $this->maniaConnect->getClientErrorText());
			return null;
		}
		return $this->maniaConnect->client->getResponse();
	}

	/**
	 * Fetch server version
	 */
	public function getVersion($forceRefresh = false) {
		if (isset($this->maniaConnect->client->version) && !$forceRefresh) return $this->maniaConnect->client->version;
		if (!$this->maniaConnect->client->query('GetVersion')) {
			trigger_error("Couldn't fetch server version. " . $this->maniaConnect->getClientErrorText());
			return null;
		}
		else {
			$this->maniaConnect->client->version = $this->maniaConnect->client->getResponse();
			return $this->maniaConnect->client->version;
		}
	}

	/**
	 * Fetch server system info
	 */
	public function getSystemInfo($forceRefresh = false, &$client = null) {
		if (!$this->maniaConnect->client && !$client) return null;
		if (!$client) $client = $this->maniaConnect->client;
		if (isset($client->systemInfo) && !$forceRefresh) return $client->systemInfo;
		if (!$client->query('GetSystemInfo')) {
			trigger_error("Couldn't fetch server system info. " . $this->maniaConnect->getClientErrorText($client));
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
		if (isset($this->maniaConnect->client->networkStats) && !$forceRefresh) return $this->maniaConnect->client->networkStats;
		if (!$this->maniaConnect->client->query('GetNetworkStats')) {
			trigger_error("Couldn't fetch network stats. " . $this->maniaConnect->getClientErrorText());
			return null;
		}
		else {
			$this->maniaConnect->client->networkStats = $this->maniaConnect->client->getResponse();
			return $this->maniaConnect->client->networkStats;
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
			if (!$this->maniaConnect->client->query('GetGameMode')) {
				trigger_error("Couldn't fetch current game mode. " . $this->maniaConnect->getClientErrorText());
				return null;
			}
			$gameMode = $this->maniaConnect->client->getResponse();
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
	
	// TODO: remove getPlayer / getPlayers -> methods now in playerHandler handeld, but should be improved more
	/**
	 * Fetch player info
	 *
	 * @param string $login        	
	 * @return struct
	 */
	public function getPlayer($login, $detailed = false) {
		if (!$login) return null;
		$command = ($detailed ? 'GetDetailedPlayerInfo' : 'GetPlayerInfo');
		if (!$this->maniaConnect->client->query($command, $login)) {
			trigger_error("Couldn't player info for '" . $login . "'. " . $this->maniaConnect->getClientErrorText());
			return null;
		}
		return $this->maniaConnect->client->getResponse();
	}

	/**
	 * Fetch all players
	 */
	public function getPlayers(&$client = null, &$purePlayers = null, &$pureSpectators = null) {
		if (!$this->maniaConnect->client && !$client) return null;
		if (!$client) $client = $this->maniaConnect->client;
		$fetchLength = 30;
		$offset = 0;
		$players = array();
		if (!is_array($purePlayers)) $purePlayers = array();
		if (!is_array($pureSpectators)) $pureSpectators = array();
		$tries = 0;
		while ($tries < 10) {
			if (!$client->query('GetPlayerList', $fetchLength, $offset)) {
				trigger_error("Couldn't get player list. " . $this->maniaConnect->getClientErrorText($client));
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
		if (!$this->maniaConnect->client->query('GetValidationReplay', $login)) {
			trigger_error("Couldn't get validation replay of '" . $login . "'. " . $this->maniaConnect->getClientErrorText());
			return null;
		}
		return $this->maniaConnect->client->getResponse();
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
		if (!$this->maniaConnect->client->query('SaveBestGhostsReplay', $login, self::GHOSTREPLAYDIR . $fileName)) {
			trigger_error("Couldn't save ghost replay. " . $this->maniaConnect->getClientErrorText());
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

    //TODO: remove getMap
	/**
	 * Fetch current map
	 */
	public function getMap() {
		if (!$this->maniaConnect->client) return null;
		if (!$this->maniaConnect->client->query('GetCurrentMapInfo')) {
			trigger_error("Couldn't fetch map info. " . $this->maniaConnect->getClientErrorText());
			return null;
		}
		return $this->maniaConnect->client->getResponse();
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
		$maxWaitTime = 20;
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
								 $this->maniaConnect->getClientErrorText());
				return false;
			}
		}
		return true;
	}
}

?>
