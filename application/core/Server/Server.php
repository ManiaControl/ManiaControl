<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\FileUtil;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

require_once __DIR__ . '/ServerCommands.php';

/**
 * Class providing Information about theconnected ManiaPlanet Server
 *
 * @author steeffeen & kremsy
 */
class Server implements CallbackListener {
	
	/**
	 * Constants
	 */
	const TABLE_SERVERS = 'mc_servers';
	
	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $serverCommands = null;
	private $index = null;
	private $login = null;

	/**
	 * Construct server
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'onInit');
		
		$this->serverCommands = new ServerCommands($maniaControl);
	}

	/**
	 * Initialize necessary Database Tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SERVERS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`index`),
				UNIQUE KEY `login` (`login`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Servers' AUTO_INCREMENT=1;";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error, E_USER_ERROR);
			return false;
		}
		$statement->close();
		return true;
	}

	/**
	 * Handle OnInit Callback
	 *
	 * @param array $callback
	 */
	public function onInit(array $callback) {
		$mysqli = $this->maniaControl->database->mysqli;
		$query = "INSERT IGNORE INTO `" . self::TABLE_SERVERS . "` (
				`login`
				) VALUES (
				?
				);";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}
		$login = $this->getLogin();
		$statement->bind_param('s', $login);
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error);
			$statement->close();
			return;
		}
		$statement->close();
		return true;
	}

	/**
	 * Fetch Game Data Directory
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
	 * Fetch Maps Directory
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
	 * Checks if ManiaControl has Access to the given Directory
	 *
	 * @param string $directory
	 * @return bool
	 */
	public function checkAccess($directory) {
		if (!$directory) return false;
		return (is_dir($directory) && is_writable($directory));
	}

	/**
	 * Fetch Server Index
	 *
	 * @return int
	 */
	public function getIndex() {
		if ($this->index) return $this->index;
		$mysqli = $this->maniaControl->database->mysqli;
		$login = $this->getLogin();
		$query = "SELECT `index` FROM `" . self::TABLE_SERVERS . "`
				WHERE `login` = '" . $login . "';";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return;
		}
		$row = $result->fetch_object();
		$result->close();
		$this->index = $row->index;
		return $this->index;
	}

	/**
	 * Fetch Server Login
	 *
	 * @return string
	 */
	public function getLogin() {
		if ($this->login) return $this->login;
		$systemInfo = $this->getSystemInfo();
		if (!$systemInfo) return null;
		$this->login = $systemInfo['ServerLogin'];
		return $this->login;
	}

	/**
	 * Get the Server Info
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
	 * Get Server Options
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
	 * Fetch current Server Name
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
	 * Fetch Server Version
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
	 * Fetch Server System Info
	 *
	 * @return array
	 */
	public function getSystemInfo() {
		if (!$this->maniaControl->client->query('GetSystemInfo')) {
			trigger_error("Couldn't fetch server system info. " . $this->maniaControl->getClientErrorText($this->maniaControl->client));
			return null;
		}
		return $this->maniaControl->client->getResponse();
	}

	/**
	 * Fetch current Game Mode
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
	 * Retrieve Validation Replay for the given Player
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
	 * Retrieve Ghost Replay for the given Player
	 *
	 * @param Player $player
	 * @return string
	 */
	public function getGhostReplay(Player $player) {
		$dataDir = $this->getDataDirectory();
		if (!$this->checkAccess($dataDir)) return null;
		
		// Build file name
		$map = $this->getMap();
		$gameMode = $this->getGameMode();
		$time = time();
		$fileName = "GhostReplays/Ghost.{$player->login}.{$gameMode}.{$time}.{$map['UId']}.Replay.Gbx";
		
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
	 * Wait for the Server to have the given Status
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
		$this->maniaControl->log("Waiting for server to reach status {$statusCode}...");
		$this->maniaControl->log("Current Status: {$lastStatus}");
		while ($response['Code'] !== 4) {
			sleep(1);
			$this->maniaControl->client->query('GetStatus');
			$response = $this->maniaControl->client->getResponse();
			if ($lastStatus !== $response['Name']) {
				$this->maniaControl->log("New Status: " . $response['Name']);
				$lastStatus = $response['Name'];
			}
			if (time() - $maxWaitTime > $waitBegin) {
				// It took too long to reach the status
				trigger_error("Server couldn't reach status {$statusCode} after {$maxWaitTime} seconds! " . $this->maniaControl->getClientErrorText());
				return false;
			}
		}
		return true;
	}
}
