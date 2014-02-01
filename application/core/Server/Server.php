<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Structures\SystemInfos;

/**
 * Class providing Information about theconnected ManiaPlanet Server
 *
 * @author steeffeen & kremsy
 */
class Server implements CallbackListener {

	/**
	 * Constants
	 */
	const TABLE_SERVERS          = 'mc_servers';
	const CB_TEAM_STATUS_CHANGED = 'ServerCallback.TeamStatusChanged';

	/**
	 * Public Properties
	 */
	public $index = -1;
	public $ip = null;
	public $port = -1;
	public $p2pPort = -1;
	public $login = null;
	public $titleId = null;
	public $dataDirectory = '';
	public $serverCommands = null;
	public $usageReporter = null;


	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $teamMode = false;

	/**
	 * Construct a new Server
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		$this->serverCommands = new ServerCommands($maniaControl);
		$this->usageReporter  = new UsageReporter($maniaControl);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'onInit');
	}

	/**
	 * Refetch the Server Properties
	 */
	private function updateProperties() {
		// System info
		$systemInfo    = $this->getSystemInfo();
		$this->ip      = $systemInfo->publishedIp;
		$this->port    = $systemInfo->port;
		$this->p2pPort = $systemInfo->p2PPort;
		$this->login   = $systemInfo->serverLogin;
		$this->titleId = $systemInfo->titleId;

		// Database index
		$mysqli    = $this->maniaControl->database->mysqli;
		$query     = "INSERT INTO `" . self::TABLE_SERVERS . "` (
				`login`
				) VALUES (
				?
				) ON DUPLICATE KEY UPDATE
				`index` = LAST_INSERT_ID(`index`);";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}
		$statement->bind_param('s', $this->login);
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error);
			$statement->close();
			return;
		}
		$this->index = $statement->insert_id;
		$statement->close();
	}

	/**
	 * Initialize necessary Database Tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli    = $this->maniaControl->database->mysqli;
		$query     = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SERVERS . "` (
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
		$this->updateProperties();
	}

	/**
	 * Set if the Server Runs a Team-Mode or not
	 *
	 * @param bool $teamMode
	 */
	public function setTeamMode($teamMode = true) {
		$oldStatus      = $this->teamMode;
		$this->teamMode = $teamMode;

		// Trigger  callback
		if ($oldStatus != $this->teamMode) {
			$this->maniaControl->callbackManager->triggerCallback(self::CB_TEAM_STATUS_CHANGED, array(self::CB_TEAM_STATUS_CHANGED, $teamMode));
		}
	}

	/**
	 * Check if the Server Runs a TeamMode
	 *
	 * @return bool
	 */
	public function isTeamMode() {
		return $this->teamMode;
	}


	/**
	 * Fetch Game Data Directory
	 *
	 * @return string
	 */
	public function getDataDirectory() {
		if ($this->dataDirectory == '') {
			try {
				$this->dataDirectory = $this->maniaControl->client->gameDataDirectory();
			} catch(\Exception $e) {
				trigger_error("Couldn't get data directory. " . $e->getMessage());
				return null;
			}
		}
		return $this->dataDirectory;
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
		return "{$dataDirectory}Maps/";
	}

	/**
	 * Checks if ManiaControl has Access to the given Directory
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
	 * Get the Server Info
	 *
	 * @param bool $detailed
	 * @return array
	 */
	public function getInfo($detailed = false) {
		if ($detailed) {
			$login = $this->login;
			try {
				$info = $this->maniaControl->client->getDetailedPlayerInfo($login);
			} catch(\Exception $e) {
				trigger_error("Couldn't fetch detailed server info. " . $e->getMessage());
				return null;
			}
			return $info;
		}
		try {
			$info = $this->maniaControl->client->getMainServerPlayerInfo();
		} catch(\Exception $e) {
			trigger_error("Couldn't fetch server info. " . $e->getMessage());
			return null;
		}
		return $info;
	}

	/**
	 * Get Server Options
	 *
	 * @return array
	 */
	public function getOptions() {
		try {
			$options = $this->maniaControl->client->getServerOptions();
		} catch(\Exception $e) {
			trigger_error("Couldn't fetch server options. " . $e->getMessage());
			return null;
		}

		return $options;
	}

	/**
	 * Fetch current Server Name
	 *
	 * @return string
	 */
	public function getName() {
		try {
			$name = $this->maniaControl->client->getServerName();
		} catch(\Exception $e) {
			trigger_error("Couldn't fetch server name. " . $e->getMessage());
			return null;
		}
		return $name;
	}


	/**
	 * Fetch Server Version
	 *
	 * @return string
	 */
	public function getVersion() {
		try {
			$version = $this->maniaControl->client->getVersion();
		} catch(\Exception $e) {
			trigger_error("Couldn't fetch server version. " . $e->getMessage());
			return null;
		}
		return $version;
	}

	/**
	 * Fetch Server System Info
	 *
	 * @return SystemInfos
	 */
	public function getSystemInfo() {
		try {
			$systemInfo = $this->maniaControl->client->getSystemInfo();
		} catch(\Exception $e) {
			trigger_error("Couldn't fetch server system info. " . $e->getMessage());
			return null;
		}

		return $systemInfo;
	}

	/**
	 * Fetch current Game Mode
	 *
	 * @param bool $stringValue
	 * @param int  $parseValue
	 * @return int | string
	 */
	public function getGameMode($stringValue = false, $parseValue = null) {
		if (is_int($parseValue)) {
			$gameMode = $parseValue;
		} else {
			try {
				$gameMode = $this->maniaControl->client->getGameMode();
			} catch(\Exception $e) {
				trigger_error("Couldn't fetch current game mode. " . $e->getMessage());
				return null;
			}
		}
		if ($stringValue) {
			switch($gameMode) {
				case 0:
					return 'Script';
				case 1:
					return 'Rounds';
				case 2:
					return 'TimeAttack';
				case 3:
					return 'Team';
				case 4:
					return 'Laps';
				case 5:
					return 'Cup';
				case 6:
					return 'Stunts';
				default:
					return 'Unknown';
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
		try {
			$replay = $this->maniaControl->client->getValidationReplay($player->login);
		} catch(\Exception $e) {
			trigger_error("Couldn't get validation replay of '{$player->login}'. " . $e->getMessage());
			return null;
		}
		return $replay;
	}

	/**
	 * Retrieve Ghost Replay for the given Player
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
		$map      = $this->maniaControl->mapManager->getCurrentMap();
		$gameMode = $this->getGameMode();
		$time     = time();
		$fileName = "GhostReplays/Ghost.{$player->login}.{$gameMode}.{$time}.{$map->uid}.Replay.Gbx";

		// Save ghost replay
		try {
			$this->maniaControl->client->saveBestGhostsReplay($player->login, $fileName);
		} catch(\Exception $e) {
			trigger_error("Couldn't save ghost replay. " . $e->getMessage());
			return null;
		}

		// Load replay file
		$ghostReplay = file_get_contents("{$dataDir}Replays/{$fileName}");
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
		$response = $this->maniaControl->client->getStatus();
		// Check if server has the given status
		if ($response->code === 4) {
			return true;
		}
		// Server not yet in given status - Wait for it...
		$waitBegin   = time();
		$maxWaitTime = 20;
		$lastStatus  = $response['Name'];
		$this->maniaControl->log("Waiting for server to reach status {$statusCode}...");
		$this->maniaControl->log("Current Status: {$lastStatus}");
		while($response->code !== 4) {
			sleep(1);
			$response = $this->maniaControl->client->getStatus();
			if ($lastStatus !== $response['Name']) {
				$this->maniaControl->log("New Status: {$response['Name']}");
				$lastStatus = $response['Name'];
			}
			if (time() - $maxWaitTime > $waitBegin) {
				// It took too long to reach the status
				trigger_error("Server couldn't reach status {$statusCode} after {$maxWaitTime} seconds! ");
				return false;
			}
		}
		return true;
	}
}
