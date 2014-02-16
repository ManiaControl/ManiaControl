<?php

namespace ManiaControl\Maps;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Files\FileUtil;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\ManiaExchange\ManiaExchangeList;
use ManiaControl\ManiaExchange\ManiaExchangeManager;
use ManiaControl\ManiaExchange\MXMapInfo;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\InvalidArgumentException;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

/**
 * Manager for Maps
 *
 * @author kremsy & steeffeen
 */
class MapManager implements CallbackListener {
	/**
	 * Constants
	 */
	const TABLE_MAPS                      = 'mc_maps';
	const CB_BEGINMAP                     = 'MapManager.BeginMap';
	const CB_MAPS_UPDATED                 = 'MapManager.MapsUpdated';
	const CB_KARMA_UPDATED                = 'MapManager.KarmaUpdated';
	const SETTING_PERMISSION_ADD_MAP      = 'Add Maps';
	const SETTING_PERMISSION_REMOVE_MAP   = 'Remove Maps';
	const SETTING_PERMISSION_SHUFFLE_MAPS = 'Shuffle Maps';
	const SETTING_PERMISSION_CHECK_UPDATE = 'Check Map Update';
	const SETTING_PERMISSION_SKIP_MAP     = 'Skip Map';
	const SETTING_PERMISSION_RESTART_MAP  = 'Restart Map';

	/**
	 * Public Properties
	 */
	public $mapQueue = null;
	public $mapCommands = null;
	public $mapList = null;
	public $mxList = null;
	public $mxManager = null;

	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $maps = array();
	/** @var Map $currentMap */
	private $currentMap = null;

	/**
	 * Construct map manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		// Create map commands instance
		$this->mxManager   = new ManiaExchangeManager($this->maniaControl);
		$this->mapList     = new MapList($this->maniaControl);
		$this->mxList      = new ManiaExchangeList($this->maniaControl);
		$this->mapCommands = new MapCommands($maniaControl);
		$this->mapQueue    = new MapQueue($this->maniaControl);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MAPLISTMODIFIED, $this, 'mapsModified');

		//Define Rights
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_ADD_MAP, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_REMOVE_MAP, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_SHUFFLE_MAPS, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_CHECK_UPDATE, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_SKIP_MAP, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_RESTART_MAP, AuthenticationManager::AUTH_LEVEL_MODERATOR);
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_MAPS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`mxid` int(11),
				`uid` varchar(50) NOT NULL,
				`name` varchar(150) NOT NULL,
				`authorLogin` varchar(100) NOT NULL,
				`fileName` varchar(100) NOT NULL,
				`environment` varchar(50) NOT NULL,
				`mapType` varchar(50) NOT NULL,
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `uid` (`uid`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Map Data' AUTO_INCREMENT=1;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		return $result;
	}

	/**
	 * Save a Map in the Database
	 *
	 * @param \ManiaControl\Maps\Map $map
	 * @return bool
	 */
	private function saveMap(Map &$map) {
		$mysqli   = $this->maniaControl->database->mysqli;
		$mapQuery = "INSERT INTO `" . self::TABLE_MAPS . "` (
				`uid`,
				`name`,
				`authorLogin`,
				`fileName`,
				`environment`,
				`mapType`
				) VALUES (
				?, ?, ?, ?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`index` = LAST_INSERT_ID(`index`),
				`fileName` = VALUES(`fileName`),
				`environment` = VALUES(`environment`),
				`mapType` = VALUES(`mapType`);";

		$mapStatement = $mysqli->prepare($mapQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$mapStatement->bind_param('ssssss', $map->uid, $map->name, $map->authorLogin, $map->fileName, $map->environment, $map->mapType);
		$mapStatement->execute();
		if ($mapStatement->error) {
			trigger_error($mapStatement->error);
			$mapStatement->close();
			return false;
		}
		$map->index = $mapStatement->insert_id;
		$mapStatement->close();
		return true;
	}

	/**
	 * Updates the Timestamp of a map
	 *
	 * @param $map
	 * @return bool
	 */
	private function updateMapTimestamp($uid) {
		$mysqli   = $this->maniaControl->database->mysqli;
		$mapQuery = "UPDATE `" . self::TABLE_MAPS . "` SET mxid = 0, changed = NOW() WHERE 'uid' = ?";

		$mapStatement = $mysqli->prepare($mapQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$mapStatement->bind_param('s', $uid);
		$mapStatement->execute();
		if ($mapStatement->error) {
			trigger_error($mapStatement->error);
			$mapStatement->close();
			return false;
		}
		$mapStatement->close();
		return true;
	}

	/**
	 * Updates a Map from Mania Exchange
	 *
	 * @param Player $admin
	 * @param        $mxId
	 * @param        $uid
	 */
	public function updateMap(Player $admin, $uid) {
		$this->updateMapTimestamp($uid);

		if (!isset($uid)) {
			trigger_error("Error while updating Map, unkown UID: " . $uid);
			$this->maniaControl->chat->sendError("Error while updating Map.", $admin->login);
			return;
		}

		$map = $this->maps[$uid];
		/** @var Map $map */
		$mxId = $map->mx->id;
		$this->removeMap($admin, $uid, true, false);
		$this->addMapFromMx($mxId, $admin->login, true);
	}

	/**
	 * Remove a Map
	 *
	 * @param \ManiaControl\Players\Player $admin
	 * @param string                       $uid
	 * @param bool                         $eraseFile
	 * @param bool                         $message
	 */
	public function removeMap(Player $admin, $uid, $eraseFile = false, $message = true) {
		$map = $this->maps[$uid];

		//Unset the Map everywhere
		$this->mapQueue->removeFromMapQueue($admin, $map->uid);

		if ($map->mx != null) {
			$this->mxManager->unsetMap($map->mx->id);
		}

		// Remove map
		$this->maniaControl->client->removeMap($map->fileName);

		if ($eraseFile) {
			// Check if ManiaControl can even write to the maps dir
			$mapDir = $this->maniaControl->client->getMapsDirectory();

			// Delete map file
			if (!@unlink($mapDir . $map->fileName)) {
				trigger_error("Couldn't remove Map '{$mapDir}{$map->fileName}'.");
				$this->maniaControl->chat->sendError("ManiaControl couldn't remove the MapFile.", $admin->login);
				return;
			}
		}

		//Show Message
		if ($message) {
			$message = '$<' . $admin->nickname . '$> removed $<' . $map->name . '$>!';
			$this->maniaControl->chat->sendSuccess($message);
			$this->maniaControl->log($message, true);
		}

		unset($this->maps[$uid]);
	}


	/**
	 * Restructures the Maplist
	 */
	public function restructureMapList() {
		$currentIndex = $this->getMapIndex($this->currentMap);

		//No RestructureNeeded
		if ($currentIndex < 14) {
			return true;
		}

		$lowerMapArray  = array();
		$higherMapArray = array();

		$i = 0;
		foreach($this->maps as $map) {
			/** @var Map $map */
			if ($i < $currentIndex) {
				$lowerMapArray[] = $map->fileName;
			} else {
				$higherMapArray[] = $map->fileName;
			}
			$i++;
		}

		$mapArray = array_merge($higherMapArray, $lowerMapArray);

		try {
			$this->maniaControl->client->chooseNextMapList($mapArray);
		} catch(Exception $e) {
			trigger_error("Error while restructuring the Maplist. " . $e->getMessage());
			return false;
		}
		return true;
	}

	/**
	 * Shuffles the MapList
	 *
	 * @param Player $admin
	 * @return bool
	 */
	public function shuffleMapList($admin = null) {
		$shuffledMaps = $this->maps;
		shuffle($shuffledMaps);

		$mapArray = array();

		foreach($shuffledMaps as $map) {
			/** @var Map $map */
			$mapArray[] = $map->fileName;
		}

		try {
			$this->maniaControl->client->chooseNextMapList($mapArray);
		} catch(Exception $e) {
			trigger_error("Couldn't shuffle mapList. " . $e->getMessage());
			return false;
		}

		$this->fetchCurrentMap();

		if ($admin != null) {
			$message = '$<' . $admin->nickname . '$> shuffled the Maplist!';
			$this->maniaControl->chat->sendSuccess($message);
			$this->maniaControl->log($message, true);
		}

		//Restructure if needed
		$this->restructureMapList();
		return true;
	}

	/**
	 * Initializes a Map
	 *
	 * @param $rpcMap
	 * @return Map
	 */
	public function initializeMap($rpcMap) {
		$map = new Map($rpcMap);
		$this->saveMap($map);

		$mapsDirectory = $this->maniaControl->server->getMapsDirectory();
		if (is_readable($mapsDirectory . $map->fileName)) {
			$mapFetcher = new \GBXChallMapFetcher(true);
			try {
				$mapFetcher->processFile($mapsDirectory . $map->fileName);
				$map->authorNick  = FORMATTER::stripDirtyCodes($mapFetcher->authorNick);
				$map->authorEInfo = $mapFetcher->authorEInfo;
				$map->authorZone  = $mapFetcher->authorZone;
				$map->comment     = $mapFetcher->comment;
			} catch(\Exception $e) {
				// TODO: replace \Exception with api exception class (?)
				trigger_error($e->getMessage());
			}
		}
		return $map;
	}

	/**
	 * Updates the full Map list, needed on Init, addMap and on ShuffleMaps
	 */
	private function updateFullMapList() {
		$maps     = $this->maniaControl->client->getMapList(100, 0);
		$tempList = array();

		foreach($maps as $rpcMap) {
			if (array_key_exists($rpcMap->uId, $this->maps)) {
				// Map already exists, only update index
				$tempList[$rpcMap->uId] = $this->maps[$rpcMap->uId];
			} else { // Insert Map Object
				$map                 = $this->initializeMap($rpcMap);
				$tempList[$map->uid] = $map;
			}
		}

		// restore Sorted Maplist
		$this->maps = $tempList;

		// Trigger own callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_MAPS_UPDATED, array(self::CB_MAPS_UPDATED));
	}

	/**
	 * Fetch current Map
	 *
	 * @return bool
	 */
	private function fetchCurrentMap() {
		$rpcMap = $this->maniaControl->client->getCurrentMapInfo();

		if (array_key_exists($rpcMap->uId, $this->maps)) {
			$this->currentMap                = $this->maps[$rpcMap->uId];
			$this->currentMap->nbCheckpoints = $rpcMap->nbCheckpoints;
			$this->currentMap->nbLaps        = $rpcMap->nbLaps;
			return true;
		}
		$map                   = $this->initializeMap($rpcMap);
		$this->maps[$map->uid] = $map;
		$this->currentMap      = $map;
		return true;
	}

	/**
	 * Handle OnInit callback
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		$this->updateFullMapList();
		$this->fetchCurrentMap();

		//Fetch Mx Infos
		$this->mxManager->fetchManiaExchangeMapInformations();

		//Restructure Maplist
		$this->restructureMapList();

	}

	/**
	 * Get Current Map
	 *
	 * @return Map currentMap
	 */
	public function getCurrentMap() {
		return $this->currentMap;
	}

	/**
	 * Returns map By UID
	 *
	 * @param $uid
	 * @return Map array
	 */
	public function getMapByUid($uid) {
		if (!isset($this->maps[$uid])) {
			return null;
		}
		return $this->maps[$uid];
	}

	/**
	 * Handle BeginMap callback
	 *
	 * @param array $callback
	 */
	public function handleBeginMap(array $callback) {
		if (!isset($callback[1][0]["UId"])) { //TODO why this can happen?
			return;
		}
		if (array_key_exists($callback[1][0]["UId"], $this->maps)) {
			// Map already exists, only update index
			$this->currentMap = $this->maps[$callback[1][0]["UId"]];
		} else {
			// can this ever happen?
			$this->fetchCurrentMap();
		}

		//Restructure MapList if id is over 15
		$this->restructureMapList();

		//Update the mx of the map (for update checks, etc.)
		$this->mxManager->fetchManiaExchangeMapInformations($this->currentMap);

		// Trigger own BeginMap callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_BEGINMAP, array(self::CB_BEGINMAP, $this->currentMap));

	}

	/**
	 * Handle Maps Modified Callback
	 *
	 * @param array $callback
	 */
	public function mapsModified(array $callback) {
		$this->updateFullMapList();
	}

	/**
	 * @return array
	 */
	public function getMaps() {
		return array_values($this->maps);
	}

	/**
	 * Returns the MapIndex of a given map
	 *
	 * @param Map $map
	 * @internal param $uid
	 * @return mixed
	 */
	public function getMapIndex(Map $map) {
		$maps = $this->getMaps();
		return array_search($map, $maps);
	}

	/**
	 * Adds a Map from Mania Exchange
	 *
	 * @param      $mapId
	 * @param      $login
	 * @param bool $update
	 */
	public function addMapFromMx($mapId, $login, $update = false) {
		$mapDir = $this->maniaControl->client->getMapsDirectory();

		if (is_numeric($mapId)) {
			// Check if map exists
			$this->maniaControl->mapManager->mxManager->getMapInfo($mapId, function (MXMapInfo $mapInfo) use ($login) {
				if (!$mapInfo || !isset($mapInfo->uploaded)) {
					// Invalid id
					$this->maniaControl->chat->sendError('Invalid MX-Id!', $login);
					return;
				}

				//Download the file
				$function = function ($file, $error) use (&$login, &$mapInfo, &$mapDir, &$update) {
					if (!$file) {
						// Download error
						$this->maniaControl->chat->sendError('Download failed!', $login);
						return;
					}
					$this->processMapFile($file, $mapInfo, $mapDir, $login, $update);
				};
				$this->maniaControl->fileReader->loadFile($mapInfo->downloadurl, $function);
			});
		}
	}

	/**
	 * Process the MapFile
	 *
	 * @param           $file
	 * @param MXMapInfo $mapInfo
	 * @param           $mapDir
	 * @param           $login
	 * @param           $update
	 * @throws \Exception
	 * @throws \Maniaplanet\DedicatedServer\Xmlrpc\Exception
	 */
	private function processMapFile($file, MXMapInfo $mapInfo, $mapDir, $login, $update) {
		//Check if map is already on the server
		if ($this->getMapByUid($mapInfo->uid) != null) {
			// Download error
			$this->maniaControl->chat->sendError('Map is already on the server!', $login);
			return;
		}

		// Save map
		$fileName = $mapInfo->id . '_' . $mapInfo->name . '.Map.Gbx';
		$fileName = FileUtil::getClearedFileName($fileName);

		$downloadDirectory = $this->maniaControl->settingManager->getSetting($this, 'MapDownloadDirectory', 'MX');

		$mapFileName = $downloadDirectory . '/' . $fileName;

		//Check if it can get locally Written
		if (is_dir($mapDir)) {
			// Create download directory if necessary
			if (!is_dir($mapDir . $downloadDirectory) && !mkdir($mapDir . $downloadDirectory)) {
				trigger_error("ManiaControl doesn't have to rights to save maps in '{$mapDir}{$downloadDirectory}'.");
				$this->maniaControl->chat->sendError("ManiaControl doesn't have the rights to save maps.", $login);
				return;
			}

			$mapDir .= $downloadDirectory . '/';

			if (!file_put_contents($mapDir . $fileName, $file)) {
				// Save error
				$this->maniaControl->chat->sendError('Saving map failed!', $login);
				return;
			}
			//Write via Write File Method
		} else {
			try {
				$this->maniaControl->client->writeFileFromString($mapFileName, $file);
			} catch(InvalidArgumentException $e) {
				if ($e->getMessage() == 'data are too big') {
					$this->maniaControl->chat->sendError("Map is too big for a remote save.", $login);
					return;
				}
				throw $e;
			}
		}

		// Check for valid map
		try {
			$this->maniaControl->client->checkMapForCurrentServerParams($mapFileName);
		} catch(Exception $e) {
			trigger_error("Couldn't check if map is valid ('{$mapFileName}'). " . $e->getMessage());
			$this->maniaControl->chat->sendError('Wrong MapType or not validated!', $login);
			return;
		}

		// Add map to map list
		try {
			$this->maniaControl->client->insertMap($mapFileName);
		} catch(Exception $e) {
			// TODO: is it even possible that an exception other than connection errors will be thrown? - remove try-catch?
			$this->maniaControl->chat->sendError("Couldn't add map to match settings!", $login);
			return;
		}

		$this->updateFullMapList();

		//Update Mx MapInfo
		$this->maniaControl->mapManager->mxManager->updateMapObjectsWithManiaExchangeIds(array($mapInfo));

		//Update last updated time
		$map = $this->maps[$mapInfo->uid];
		/** @var Map $map */
		$map->lastUpdate = time();

		$player = $this->maniaControl->playerManager->getPlayer($login);

		if (!$update) {
			//Message
			$message = '$<' . $player->nickname . '$> added $<' . $mapInfo->name . '$>!';
			$this->maniaControl->chat->sendSuccess($message);
			$this->maniaControl->log($message, true);
			// Queue requested Map
			$this->maniaControl->mapManager->mapQueue->addMapToMapQueue($login, $mapInfo->uid);
		} else {
			$message = '$<' . $player->nickname . '$> updated $<' . $mapInfo->name . '$>!';
			$this->maniaControl->chat->sendSuccess($message);
			$this->maniaControl->log($message, true);
		}
	}
	// TODO: add local map by filename
} 
