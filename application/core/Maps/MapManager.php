<?php

namespace ManiaControl\Maps;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\FileUtil;
use ManiaControl\ManiaControl;

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
	const TABLE_MAPS                    = 'mc_maps';
	const CB_BEGINMAP                   = 'MapManager.BeginMap';
	const CB_MAPS_UPDATED               = 'MapManager.MapsUpdated';
	const CB_KARMA_UPDATED              = 'MapManager.KarmaUpdated';
	const SETTING_PERMISSION_ADD_MAP    = 'Add Maps';
	const SETTING_PERMISSION_REMOVE_MAP = 'Remove Maps';

	/**
	 * Public Properties
	 */
	public $mapQueue = null;
	public $mapCommands = null;
	public $mapList = null;
	public $mxInfoSearcher = null;

	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $maps = array();
	private $mapsUids = array();
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
		$this->mapList        = new MapList($this->maniaControl);
		$this->mapCommands    = new MapCommands($maniaControl);
		$this->mapQueue       = new MapQueue($this->maniaControl);
		$this->mxInfoSearcher = new ManiaExchangeInfoSearcher($this->maniaControl);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MAPLISTMODIFIED, $this, 'mapsModified');

		//Define Rights
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_ADD_MAP, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_REMOVE_MAP, AuthenticationManager::AUTH_LEVEL_ADMIN);
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
	 * Remove a Map
	 *
	 * @param int    $id
	 * @param string $uid
	 * @param bool   $eraseFile
	 */
	public function removeMap($id, $uid, $eraseFile = false) { //TODO erasefile?
		$map = $this->mapsUids[$uid];
		$this->maniaControl->client->query('RemoveMap', $map->fileName);
		$this->maniaControl->chat->sendSuccess('Map $<' . $map->name . '$> removed!');
		// TODO specified message, who done it?
		$this->maniaControl->log('Map $<' . $map->name . '$> removed!', true);
		unset($this->mapsUids[$uid]);
		unset($this->maps[$id]);
	}

	/**
	 * Updates the full Map list, needed on Init, addMap and on ShuffleMaps
	 */
	private function updateFullMapList() {
		if(!$this->maniaControl->client->query('GetMapList', 100, 0)) {
			trigger_error("Couldn't fetch mapList. " . $this->maniaControl->getClientErrorText());
			return null;
		}

		$tempList = array();

		$maps = $this->maniaControl->client->getResponse();
		foreach($maps as $rpcMap) {
			if(array_key_exists($rpcMap["UId"], $this->mapsUids)) {
				// Map already exists, only update index
				$tempList[] = $this->mapsUids[$rpcMap["UId"]];
			} else { // Insert Map Object
				$map = new Map($this->maniaControl, $rpcMap);
				$this->saveMap($map);
				$tempList[]                = $map;
				$this->mapsUids[$map->uid] = $map;
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
		if(!$this->maniaControl->client->query('GetCurrentMapInfo')) {
			trigger_error("Couldn't fetch map info. " . $this->maniaControl->getClientErrorText());
			return false;
		}
		$rpcMap = $this->maniaControl->client->getResponse();
		if(array_key_exists($rpcMap["UId"], $this->mapsUids)) {
			$this->currentMap = $this->mapsUids[$rpcMap["UId"]];
			return true;
		}
		$map = new Map($this->maniaControl, $rpcMap);
		$this->saveMap($map);
		$this->mapsUids[$map->uid] = $map;
		$this->maps[]              = $map;
		$this->currentMap          = $map;
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
	 * @return mixed
	 */
	public function getMapByUid($uid) {
		if(!isset($this->mapsUids[$uid])) {
			return null;
		}
		return $this->mapsUids[$uid];
	}

	/**
	 * Handle BeginMap callback
	 *
	 * @param array $callback
	 */
	public function handleBeginMap(array $callback) {
		if(array_key_exists($callback[1][0]["UId"], $this->mapsUids)) {
			// Map already exists, only update index
			$this->currentMap = $this->mapsUids[$callback[1][0]["UId"]];
		} else {
			// can this ever happen?
			$this->fetchCurrentMap();
		}

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
		return $this->maps;
	}

	/**
	 * Adds a Map from Mania Exchange
	 *
	 * @param $mapId
	 * @param $login
	 */
	public function addMapFromMx($mapId, $login) {
		// Check if ManiaControl can even write to the maps dir
		if(!$this->maniaControl->client->query('GetMapsDirectory')) {
			trigger_error("Couldn't get map directory. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("ManiaControl couldn't retrieve the maps directory.", $login);
			return;
		}

		$mapDir = $this->maniaControl->client->getResponse();
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
			$url = "http://api.mania-exchange.com/{$title}/maps/{$mapId}?format=json";

			$mapInfo = FileUtil::loadFile($url, "application/json");

			if(!$mapInfo || strlen($mapInfo) <= 0) {
				// Invalid id
				$this->maniaControl->chat->sendError('Invalid MX-Id!', $login);
				return;
			}

			$mapInfo = json_decode($mapInfo, true);
			$mapInfo = $mapInfo[0];

			$url  = "http://{$title}.mania-exchange.com/tracks/download/{$mapId}";
			$file = FileUtil::loadFile($url);
			if(!$file) {
				// Download error
				$this->maniaControl->chat->sendError('Download failed!', $login);
				return;
			}
			// Save map
			$fileName = $mapId . '_' . $mapInfo['Name'] . '.Map.Gbx';
			$fileName = FileUtil::getClearedFileName($fileName);
			if(!file_put_contents($mapDir . $fileName, $file)) {
				// Save error
				$this->maniaControl->chat->sendError('Saving map failed!', $login);
				return;
			}
			// Check for valid map
			$mapFileName = $downloadDirectory . '/' . $fileName;

			if(!$this->maniaControl->client->query('CheckMapForCurrentServerParams', $mapFileName)) {
				trigger_error("Couldn't check if map is valid ('{$mapFileName}'). " . $this->maniaControl->getClientErrorText());
				$this->maniaControl->chat->sendError('Error checking map!', $login);
				return;
			}
			$response = $this->maniaControl->client->getResponse();
			if(!$response) {
				// Invalid map type
				$this->maniaControl->chat->sendError("Invalid map type.", $login);
				return;
			}
			// Add map to map list
			if(!$this->maniaControl->client->query('InsertMap', $mapFileName)) {
				$this->maniaControl->chat->sendError("Couldn't add map to match settings!", $login);
				return;
			}
			$this->maniaControl->chat->sendSuccess('Map $<' . $mapInfo['Name'] . '$> added!');

			$this->updateFullMapList();

			// Queue requested Map
			$this->maniaControl->mapManager->mapQueue->addMapToMapQueue($login, $mapInfo['MapUID']);
		}
		// TODO: add local map by filename
	}
} 
