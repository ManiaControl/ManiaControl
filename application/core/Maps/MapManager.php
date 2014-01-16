<?php

namespace ManiaControl\Maps;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\FileUtil;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

require_once __DIR__ . '/Map.php';
require_once __DIR__ . '/MapCommands.php';
require_once __DIR__ . '/MapList.php';
require_once __DIR__ . '/MapQueue.php';

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

	/**
	 * Public Properties
	 */
	public $mapQueue = null;
	public $mapCommands = null;
	public $mapList = null;
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
		$this->mapList     = new MapList($this->maniaControl);
		$this->mapCommands = new MapCommands($maniaControl);
		$this->mapQueue    = new MapQueue($this->maniaControl);
		$this->mxManager   = new ManiaExchangeManager($this->maniaControl);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MAPLISTMODIFIED, $this, 'mapsModified');

		//Define Rights
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_ADD_MAP, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_REMOVE_MAP, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_SHUFFLE_MAPS, AuthenticationManager::AUTH_LEVEL_ADMIN);
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
				`uid` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`name` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
				`authorLogin` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`fileName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`environment` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`mapType` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `uid` (`uid`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Map data' AUTO_INCREMENT=1;";
		$result = $mysqli->query($query);
		if($mysqli->error) {
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
		$mysqli       = $this->maniaControl->database->mysqli;
		$mapQuery     = "INSERT INTO `" . self::TABLE_MAPS . "` (
				`uid`,
				`name`,
				`authorLogin`,
				`fileName`,
				`environment`,
				`mapType`
				) VALUES (
				?, ?, ?, ?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`index` = LAST_INSERT_ID(`index`);";
		$mapStatement = $mysqli->prepare($mapQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$mapStatement->bind_param('ssssss', $map->uid, $map->name, $map->authorLogin, $map->fileName, $map->environment, $map->mapType);
		$mapStatement->execute();
		if($mapStatement->error) {
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
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$mapStatement->bind_param('s', $uid);
		$mapStatement->execute();
		if($mapStatement->error) {
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

		$mapsDirectory = $this->maniaControl->server->getMapsDirectory();
		if(!$this->maniaControl->server->checkAccess($mapsDirectory)) {
			$this->maniaControl->chat->sendError("ManiaControl doesn't have access to the maps directory.", $admin->login);
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
	public function removeMap(Player $admin, $uid, $eraseFile = false, $message = true) { //TODO erasefile?
		$map = $this->maps[$uid];

		//Unset the Map everywhere
		$this->mapQueue->removeFromMapQueue($admin->login, $map->uid);
		$this->mxManager->unsetMap($map->mx->id);

		// Remove map
		if(!$this->maniaControl->client->removeMap($map->fileName)) {
			trigger_error("Couldn't remove current map. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("Couldn't remove map.", $admin);
			return;
		}

		//Show Message
		if($message) {
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
		if($currentIndex < 14) {
			return true;
		}

		$lowerMapArray  = array();
		$higherMapArray = array();

		$i = 0;
		foreach($this->maps as $map) {
			/** @var Map $map */
			if($i < $currentIndex) {
				$lowerMapArray[] = $map->fileName;
			} else {
				$higherMapArray[] = $map->fileName;
			}
			$i++;
		}

		$mapArray = array_merge($higherMapArray, $lowerMapArray);

		if(!$this->maniaControl->client->chooseNextMapList($mapArray)) {
			trigger_error("Error while restructuring the Maplist. " . $this->maniaControl->getClientErrorText());
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

		if(!$this->maniaControl->client->chooseNextMapList($mapArray)) {
			trigger_error("Couldn't shuffle mapList. " . $this->maniaControl->getClientErrorText());
			return false;
		}

		$this->fetchCurrentMap();

		if($admin != null) {
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
		if($this->maniaControl->server->checkAccess($mapsDirectory)) {
			$mapFetcher = new \GBXChallMapFetcher(true);
			try {
				$mapFetcher->processFile($mapsDirectory . $map->fileName);
				$map->authorNick  = FORMATTER::stripDirtyCodes($mapFetcher->authorNick);
				$map->authorEInfo = $mapFetcher->authorEInfo;
				$map->authorZone  = $mapFetcher->authorZone;
				$map->comment     = $mapFetcher->comment;
			} catch(\Exception $e) {
				trigger_error($e->getMessage());
			}
		}
		return $map;
	}

	/**
	 * Updates the full Map list, needed on Init, addMap and on ShuffleMaps
	 */
	private function updateFullMapList() {
		if(!$maps = $this->maniaControl->client->getMapList(100, 0)) {
			trigger_error("Couldn't fetch mapList. " . $this->maniaControl->getClientErrorText());
			return null;
		}

		$tempList = array();

		foreach($maps as $rpcMap) {
			if(array_key_exists($rpcMap->uId, $this->maps)) {
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
		if(!$rpcMap = $this->maniaControl->client->getCurrentMapInfo()) {
			trigger_error("Couldn't fetch map info. " . $this->maniaControl->getClientErrorText());
			return false;
		}
		if(array_key_exists($rpcMap->uId, $this->maps)) {
			$this->currentMap = $this->maps[$rpcMap->uId];
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
		if(!isset($this->maps[$uid])) {
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
		if(array_key_exists($callback[1][0]["UId"], $this->maps)) {
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
	 *
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
		// Check if ManiaControl can even write to the maps dir
		if(!$mapDir = $this->maniaControl->client->getMapsDirectory()) {
			trigger_error("Couldn't get map directory. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("ManiaControl couldn't retrieve the maps directory.", $login);
			return;
		}

		if(!is_dir($mapDir)) {
			trigger_error("ManiaControl doesn't have have access to the maps directory in '{$mapDir}'.");
			$this->maniaControl->chat->sendError("ManiaControl doesn't have access to the maps directory.", $login);
			return;
		}
		$downloadDirectory = $this->maniaControl->settingManager->getSetting($this, 'MapDownloadDirectory', 'MX');
		// Create download directory if necessary
		if(!is_dir($mapDir . $downloadDirectory) && !mkdir($mapDir . $downloadDirectory)) {
			trigger_error("ManiaControl doesn't have to rights to save maps in '{$mapDir}{$downloadDirectory}'.");
			$this->maniaControl->chat->sendError("ManiaControl doesn't have the rights to save maps.", $login);
			return;
		}
		$mapDir .= $downloadDirectory . '/';

		// Download the map
		if(is_numeric($mapId)) {
			// Load from MX
			$serverInfo = $this->maniaControl->server->getSystemInfo();
			$title      = strtolower(substr($serverInfo['TitleId'], 0, 2));

			// Check if map exists
			$mxMapInfos = $this->maniaControl->mapManager->mxManager->getMaplistByMixedUidIdString($mapId);
			$mapInfo    = $mxMapInfos[0];
			/** @var MXMapInfo $mapInfo */

			if(!$mapInfo || !isset($mapInfo->uploaded)) {
				// Invalid id
				$this->maniaControl->chat->sendError('Invalid MX-Id!', $login);
				return;
			}

			$url  = "http://{$title}.mania-exchange.com/tracks/download/{$mapId}";
			$file = FileUtil::loadFile($url);
			if(!$file) {
				// Download error
				$this->maniaControl->chat->sendError('Download failed!', $login);
				return;
			}

			//Check if map is already on the server
			if($this->getMapByUid($mapInfo->uid) != null) {
				// Download error
				$this->maniaControl->chat->sendError('Map is already on the server!', $login);
				return;
			}

			// Save map
			$fileName = $mapId . '_' . $mapInfo->name . '.Map.Gbx';
			$fileName = FileUtil::getClearedFileName($fileName);
			if(!file_put_contents($mapDir . $fileName, $file)) {
				// Save error
				$this->maniaControl->chat->sendError('Saving map failed!', $login);
				return;
			}
			// Check for valid map
			$mapFileName = $downloadDirectory . '/' . $fileName;

			if(!$response = $this->maniaControl->client->checkMapForCurrentServerParams($mapFileName)) {
				trigger_error("Couldn't check if map is valid ('{$mapFileName}'). " . $this->maniaControl->getClientErrorText());
				$this->maniaControl->chat->sendError('Error checking map!', $login);
				return;
			}

			if(!$response) {
				// Invalid map type
				$this->maniaControl->chat->sendError("Invalid map type.", $login);
				return;
			}

			// Add map to map list
			if(!$this->maniaControl->client->insertMap($mapFileName)) {
				$this->maniaControl->chat->sendError("Couldn't add map to match settings!", $login);
				return;
			}

			$this->updateFullMapList();

			//Update Mx MapInfo
			$this->maniaControl->mapManager->mxManager->updateMapObjectsWithManiaExchangeIds($mxMapInfos);

			//Update last updated time
			$map = $this->maps[$mapInfo->uid];
			/** @var Map $map */
			$map->lastUpdate = time();

			$player = $this->maniaControl->playerManager->getPlayer($login);

			if(!$update) {
				//Message
				$this->maniaControl->chat->sendSuccess('$<' . $player->nickname . '$> added $<' . $mapInfo->name . '$>!');
				// Queue requested Map
				$this->maniaControl->mapManager->mapQueue->addMapToMapQueue($login, $mapInfo->uid);
			} else {
				$this->maniaControl->chat->sendSuccess('$<' . $player->nickname . '$> updated $<' . $mapInfo->name . '$>!');
			}
		}
		// TODO: add local map by filename
	}
} 
