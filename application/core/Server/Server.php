<?php

namespace ManiaControl\Server;

use ManiaControl\FileUtil;
use ManiaControl\ManiaControl;

require_once __DIR__ . '/ServerCommands.php';

/**
 * Class providing information and commands for the connected maniaplanet server
 *
 * @author steeffeen & kremsy
 */
class Server {
	
	/**
	 * Public properties
	 */
	public $config = null;
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $serverCommands = null;

	/**
	 * Construct server
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Load config
		$this->config = FileUtil::loadConfig('server.xml');
		
		$this->serverCommands = new ServerCommands($maniaControl);
	}

	/**
	 * Fetch game data directory
	 *
	 * @return string
	 */
	public function getDataDirectory() {
		if (!$this->maniaControl->client->query('GameDataDirectory')) {
			trigger_error("Couldn't get data directory. " . $this->maniaControl->getClientErrorText());
			return null;
		}
		return $this->maniaControl->client->getResponse();
	}

	/**
	 * Fetch maps directory
	 *
	 * @return string
	 */
	public function getMapsDirectory() {
		$dataDirectory = $this->getDataDirectory();
		if (!$dataDirectory) {
			return null;
		}
		return $dataDirectory . 'Maps/';
	}

	/**
	 * Checks if ManiaControl has access to the given directory
	 *
	 * @param string $directory        	
	 * @return bool
	 */
	public function checkAccess($directory) {
		if (!$directory) {
			return false;
		}
		return (is_dir($directory) && is_writable($directory));
	}

	/**
	 * Fetch server login
	 *
	 * @return array
	 */
	public function getLogin() {
		$systemInfo = $this->getSystemInfo();
		if (!$systemInfo) {
			return null;
		}
		return $systemInfo['ServerLogin'];
	}

	/**
	 * Get server info
	 *
	 * @param bool $detailed        	
	 * @return array
	 */
	public function getInfo($detailed = false) {
		if ($detailed) {
			$login = $this->getLogin();
			if (!$this->maniaControl->client->query('GetDetailedPlayerInfo', $login)) {
				trigger_error("Couldn't fetch detailed server info. " . $this->maniaControl->getClientErrorText());
				return null;
			}
			return $this->maniaControl->client->getResponse();
		}
		if (!$this->maniaControl->client->query('GetMainServerPlayerInfo')) {
			trigger_error("Couldn't fetch server info. " . $this->maniaControl->getClientErrorText());
			return null;
		}
		return $this->maniaControl->client->getResponse();
	}

	/**
	 * Get server options
	 *
	 * @return array
	 */
	public function getOptions() {
		if (!$this->maniaControl->client->query('GetServerOptions')) {
			trigger_error("Couldn't fetch server options. " . $this->maniaControl->getClientErrorText());
			return null;
		}
		return $this->maniaControl->client->getResponse();
	}

	/**
	 * Fetch server name
	 *
	 * @return string
	 */
	public function getName() {
		if (!$this->maniaControl->client->query('GetServerName')) {
			trigger_error("Couldn't fetch server name. " . $this->maniaControl->getClientErrorText());
			return null;
		}
		return $this->maniaControl->client->getResponse();
	}

	/**
	 * Fetch server version
	 *
	 * @return string
	 */
	public function getVersion() {
		if (!$this->maniaControl->client->query('GetVersion')) {
			trigger_error("Couldn't fetch server version. " . $this->maniaControl->getClientErrorText());
			return null;
		}
		return $this->maniaControl->client->getResponse();
	}

	/**
	 * Fetch server system info
	 *
	 * @return array
	 */
	public function getSystemInfo() {
		if (!$this->maniaControl->client->query('GetSystemInfo')) {
			trigger_error("Couldn't fetch server system info. " . $this->maniaControl->getClientErrorText($client));
			return null;
		}
		return $this->maniaControl->client->getResponse();
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
			if (!$this->maniaControl->client->query('GetGameMode')) {
				trigger_error("Couldn't fetch current game mode. " . $this->maniaControl->getClientErrorText());
				return null;
			}
			$gameMode = $this->maniaControl->client->getResponse();
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
	 * Retrieve validation replay for given player
	 *
	 * @param Player $player        	
	 * @return string
	 */
	public function getValidationReplay(Player $player) {
		if (!$this->maniaControl->client->query('GetValidationReplay', $player->login)) {
			trigger_error("Couldn't get validation replay of '{$player->login}'. " . $this->maniaControl->getClientErrorText());
			return null;
		}
		return $this->maniaControl->client->getResponse();
	}

	/**
	 * Retrieve ghost replay for the given player
	 *
	 * @param Player $player        	
	 * @return string
	 */
	public function getGhostReplay(Player $player) {
		$dataDir = $this->getDataDirectory();
		if (!$this->checkAccess($dataDir)) {
			return null;
		}
		
		// Build file name
		$map = $this->getMap();
		$gameMode = $this->getGameMode();
		$time = time();
		$fileName = "GhostReplays/Ghost.{$login}.{$gameMode}.{$time}.{$map['UId']}.Replay.Gbx";
		
		// Save ghost replay
		if (!$this->maniaControl->client->query('SaveBestGhostsReplay', $player->login, $fileName)) {
			trigger_error("Couldn't save ghost replay. " . $this->maniaControl->getClientErrorText());
			return null;
		}
		
		// Load replay file
		$ghostReplay = file_get_contents($dataDir . 'Replays/' . $fileName);
		if (!$ghostReplay) {
			trigger_error("Couldn't retrieve saved ghost replay.");
			return null;
		}
		return $ghostReplay;
	}

	/**
	 * Waits for the server to have the given status
	 *
	 * @param int $statusCode        	
	 * @return bool
	 */
	public function waitForStatus($statusCode = 4) {
		$this->maniaControl->client->query('GetStatus');
		$response = $this->maniaControl->client->getResponse();
		// Check if server has the given status
		if ($response['Code'] === 4) {
			return true;
		}
		// Server not yet in given status -> Wait for it...
		$waitBegin = time();
		$maxWaitTime = 20;
		$lastStatus = $response['Name'];
		error_log("Waiting for server to reach status {$statusCode}...");
		error_log("Current Status: {$lastStatus}");
		while ($response['Code'] !== 4) {
			sleep(1);
			$this->maniaControl->client->query('GetStatus');
			$response = $this->maniaControl->client->getResponse();
			if ($lastStatus !== $response['Name']) {
				error_log("New Status: " . $response['Name']);
				$lastStatus = $response['Name'];
			}
			if (time() - $maxWaitTime > $waitBegin) {
				// It took too long to reach the status
				trigger_error(
						"Server couldn't reach status {$statusCode} after {$maxWaitTime} seconds! " .
								 $this->maniaControl->getClientErrorText());
				return false;
			}
		}
		return true;
	}
}

?>
