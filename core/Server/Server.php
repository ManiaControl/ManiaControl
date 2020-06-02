<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Commands\CommandListener;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Script\ScriptManager;
use ManiaControl\Utils\CommandLineHelper;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

/**
 * Class providing access to the connected ManiaPlanet Server
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Server implements CallbackListener, CommandListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const TABLE_SERVERS        = 'mc_servers';
	const CB_TEAM_MODE_CHANGED = 'Server.TeamModeChanged';

	/*
	 * Public properties
	 */
	/** @var Config $config */
	public $config  = null;
	public $index   = -1;
	public $ip      = null;
	public $port    = -1;
	public $p2pPort = -1;
	public $login   = null;
	public $titleId = null;

	/** @var Directory $directory */
	private $directory = null;

	/** @var Commands $commands */
	private $commands = null;

	/** @var UsageReporter $usageReporter */
	private $usageReporter = null;

	/** @var RankingManager $rankingManager */
	private $rankingManager = null;

	/** @var \ManiaControl\Script\ScriptManager $scriptManager */
	private $scriptManager = null;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $teamMode     = null;

	/**
	 * Construct a new Server
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		$this->directory      = new Directory($maniaControl);
		$this->commands       = new Commands($maniaControl);
		$this->usageReporter  = new UsageReporter($maniaControl);
		$this->rankingManager = new RankingManager($maniaControl);
		$this->scriptManager  = new ScriptManager($maniaControl);

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'onInit');

		$this->maniaControl->getCommandManager()->registerCommandListener("uptime", $this, "chatUpTime", true, "Show how long the server is running.");
	}

	/**
	 * Displays how long the Server is running already in the Chat
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function chatUpTime(array $chatCallback, Player $player) {
		$networkStats = $this->maniaControl->getClient()->getNetworkStats();

		$minutestotal = $networkStats->uptime / 60;
		$hourstotal   = $minutestotal / 60;
		$days         = intval($hourstotal / 24);
		$hours        = intval($hourstotal - 24 * $days);
		$minutes      = intval($minutestotal - 24 * 60 * $days - $hours * 60);

		$dayString    = ($days    == 1 ? 'day'    : 'days'   );
		$hourString   = ($hours   == 1 ? 'hour'   : 'hours'  );
		$minuteString = ($minutes == 1 ? 'minute' : 'minutes');

		$message = $this->maniaControl->getChat()->formatMessage(
			'Server is running since %s %s, %s %s and %s %s',
			$days,
			$dayString,
			$hours,
			$hourString,
			$minutes,
			$minuteString
		);
		$this->maniaControl->getChat()->sendChat($message, $player);
	}

	/**
	 * Initialize necessary Database Tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli    = $this->maniaControl->getDatabase()->getMysqli();
		$query     = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SERVERS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`login` varchar(100) NOT NULL,
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
	 * Return the server config
	 *
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Return the server commands
	 *
	 * @return Commands
	 */
	public function getCommands() {
		return $this->commands;
	}

	/**
	 * Return the usage reporter
	 *
	 * @return UsageReporter
	 */
	public function getUsageReporter() {
		return $this->usageReporter;
	}

	/**
	 * Return the ranking manager
	 *
	 * @return RankingManager
	 */
	public function getRankingManager() {
		return $this->rankingManager;
	}

	/**
	 * Return the script manager
	 *
	 * @return \ManiaControl\Script\ScriptManager
	 */
	public function getScriptManager() {
		return $this->scriptManager;
	}

	/**
	 * Load the server configuration from the config XML
	 *
	 * @return Config
	 */
	public function loadConfig() {
		// Server id parameter
		$serverId = CommandLineHelper::getParameter('-id');

		// Server xml element with given id
		$serverElement = null;
		if ($serverId) {
			$serverElements = $this->maniaControl->getConfig()->xpath("server[@id='{$serverId}']");
			if (!$serverElements) {
				$this->maniaControl->quit("No Server configured with the ID '{$serverId}'!", true);
			}
			$serverElement = $serverElements[0];
		} else {
			$serverElements = $this->maniaControl->getConfig()->xpath('server');
			if (!$serverElements) {
				$this->maniaControl->quit('Invalid server configuration (No Server configured).', true);
			}
			$serverElement = $serverElements[0];
		}

		// Get config elements
		$hostElements = $serverElement->xpath('host');
		$portElements = $serverElement->xpath('port');
		$userElements = $serverElement->xpath('user');
		if (!$userElements) {
			$userElements = $serverElement->xpath('login');
		}
		$passElements = $serverElement->xpath('pass');

		// Create config object
		$config  = new Config($serverId, $hostElements, $portElements, $userElements, $passElements);
		$message = null;
		if (!$config->validate($message)) {
			$this->maniaControl->quit("Your config file doesn't seem to be maintained properly. Please check the server configuration again! {$message}", true);
		}
		$this->config = $config;
		return $this->config;
	}

	/**
	 * Gets all Servers from the Database
	 *
	 * @return \stdClass[]
	 */
	public function getAllServers() {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "SELECT * FROM `" . self::TABLE_SERVERS . "`;";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return array();
		}

		$servers = array();
		while ($row = $result->fetch_object()) {
			array_push($servers, $row);
		}
		$result->free();

		return $servers;
	}

	/** Get Server Login by Index
	 *
	 * @param int $index
	 * @return string
	 */
	public function getServerLoginByIndex($index) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "SELECT * FROM `" . self::TABLE_SERVERS . "` WHERE `index`=" . $index . ";";
		$result = $mysqli->query($query);

		if (!$result) {
			trigger_error($mysqli->error);
			return "";
		}

		if ($result->num_rows != 1) {
			return "";
		}

		$row = $result->fetch_object();

		return $row->login;
	}

	/**
	 * Handle OnInit Callback
	 */
	public function onInit() {
		$this->updateProperties();
	}

	/**
	 * Refetch the Server Properties
	 */
	private function updateProperties() {
		// System info
		$systemInfo    = $this->maniaControl->getClient()->getSystemInfo();
		$this->ip      = $systemInfo->publishedIp;
		$this->port    = $systemInfo->port;
		$this->p2pPort = $systemInfo->p2PPort;
		$this->login   = $systemInfo->serverLogin;
		$this->titleId = $systemInfo->titleId;

		// Database index
		$mysqli    = $this->maniaControl->getDatabase()->getMysqli();
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
	 * Get Server Player Info
	 *
	 * @return \Maniaplanet\DedicatedServer\Structures\PlayerDetailedInfo
	 */
	public function getInfo() {
		return $this->maniaControl->getClient()->getDetailedPlayerInfo($this->login);
	}

	/**
	 * Retrieve Validation Replay for the given Player
	 *
	 * @param string $login
	 * @return string
	 */
	public function getValidationReplay($login) {
		$login = Player::parseLogin($login);
		try {
			$replay = $this->maniaControl->getClient()->getValidationReplay($login);
		} catch (Exception $e) { //UnavailableFeature Exception
			$this->maniaControl->getErrorHandler()->triggerDebugNotice("Exception line 330 Server.php" . $e->getMessage());
			Logger::logError("Couldn't get validation replay of '{$login}'. " .  $e->getMessage());
			return null;
		}
		return $replay;
	}

	/**
	 * Retrieve Ghost Replay for the given Player
	 *
	 * @param string $login
	 * @return string
	 */
	public function getGhostReplay($login) {
		$dataDir = $this->getDirectory()->getUserDataFolder();
		if (!$this->checkAccess($dataDir)) {
			return null;
		}

		// Build file name
		$login    = Player::parseLogin($login);
		$map      = $this->maniaControl->getMapManager()->getCurrentMap();
		$gameMode = $this->getGameMode();
		$time     = time();
		$fileName = "GhostReplays/Ghost.{$login}.{$gameMode}.{$time}.{$map->uid}.Replay.Gbx";

		// Save ghost replay
		try {
			$this->maniaControl->getClient()->saveBestGhostsReplay($login, $fileName);
		} catch (Exception $e) {
			// TODO temp added 19.04.2014
			$this->maniaControl->getErrorHandler()->triggerDebugNotice("Exception line 360 Server.php" . $e->getMessage());

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
	 * Return the server directory
	 *
	 * @return Directory
	 */
	public function getDirectory() {
		return $this->directory;
	}

	/**
	 * Check if ManiaControl has Access to the given Directory
	 *
	 * @param string $directory
	 * @return bool
	 */
	public function checkAccess($directory) {
		return (is_dir($directory) && is_writable($directory));
	}

	/**
	 * Fetch the current Game Mode
	 *
	 * @param bool $stringValue
	 * @param int  $parseValue
	 * @return int | string
	 */
	public function getGameMode($stringValue = false, $parseValue = null) {
		if (is_int($parseValue)) {
			$gameMode = $parseValue;
		} else {
			$gameMode = $this->maniaControl->getClient()->getGameMode();
		}
		if ($stringValue) {
			switch ($gameMode) {
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
	 * Wait for the Server to have the given Status
	 *
	 * @param int $statusCode
	 * @return bool
	 */
	public function waitForStatus($statusCode = 4) {
		$response = $this->maniaControl->getClient()->getStatus();
		// Check if server has the given status
		if ($response->code === 4) {
			return true;
		}
		// Server not yet in given status - Wait for it...
		$waitBegin   = time();
		$maxWaitTime = 50;
		$lastStatus  = $response->name;
		Logger::log("Waiting for server to reach status {$statusCode}...");
		Logger::log("Current Status: {$lastStatus}");
		while ($response->code !== 4) {
			sleep(1);
			$response = $this->maniaControl->getClient()->getStatus();
			if ($lastStatus !== $response->name) {
				Logger::log("New Status: {$response->name}");
				$lastStatus = $response->name;
			}
			if (time() - $maxWaitTime > $waitBegin) {
				// It took too long to reach the status
				Logger::logError("Server couldn't reach status {$statusCode} after {$maxWaitTime} seconds! ");
				return false;
			}
		}
		return true;
	}

	/**
	 * Set whether the Server Runs a Team-Based Mode or not
	 *
	 * @param bool $teamMode
	 */
	public function setTeamMode($teamMode = true) {
		$oldStatus      = $this->teamMode;
		$this->teamMode = (bool) $teamMode;

		// Trigger callback
		if ($oldStatus !== $this->teamMode | $oldStatus === null) {
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_TEAM_MODE_CHANGED, $teamMode);
		}
	}

	/**
	 * Check if the Server Runs a Team-Based Mode
	 *
	 * @deprecated
	 * @see ScriptManager::modeIsTeamMode()
	 * @return bool
	 */
	public function isTeamMode() {
		return $this->teamMode;
	}

	/**
	 * Build the join link
	 *
	 * @return string
	 */
	public function getJoinLink() {
		return 'maniaplanet://#join=' . $this->login . '@' . $this->titleId;
	}

	/**
	 * Check if the Servers is empty
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return ($this->maniaControl->getPlayerManager()->getPlayerCount(false) === 0);
	}
}
