<?php

namespace ManiaControl\Maps;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Communication\CommunicationAnswer;
use ManiaControl\Communication\CommunicationListener;
use ManiaControl\Communication\CommunicationMethods;
use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\Files\FileUtil;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\ManiaExchange\ManiaExchangeList;
use ManiaControl\ManiaExchange\ManiaExchangeManager;
use ManiaControl\ManiaExchange\MXMapInfo;
use ManiaControl\Players\Player;
use ManiaControl\Utils\Formatter;
use Maniaplanet\DedicatedServer\InvalidArgumentException;
use Maniaplanet\DedicatedServer\Xmlrpc\AlreadyInListException;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;
use Maniaplanet\DedicatedServer\Xmlrpc\FileException;
use Maniaplanet\DedicatedServer\Xmlrpc\IndexOutOfBoundException;
use Maniaplanet\DedicatedServer\Xmlrpc\InvalidMapException;
use Maniaplanet\DedicatedServer\Xmlrpc\NotInListException;
use Maniaplanet\DedicatedServer\Xmlrpc\UnavailableFeatureException;

/**
 * ManiaControl Map Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapManager implements CallbackListener, CommunicationListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const TABLE_MAPS                      = 'mc_maps';
	const CB_MAPS_UPDATED                 = 'MapManager.MapsUpdated';
	const CB_KARMA_UPDATED                = 'MapManager.KarmaUpdated';
	const SETTING_PERMISSION_ADD_MAP      = 'Add Maps';
	const SETTING_PERMISSION_REMOVE_MAP   = 'Remove Maps';
	const SETTING_PERMISSION_ERASE_MAP    = 'Erase Maps';
	const SETTING_PERMISSION_SHUFFLE_MAPS = 'Shuffle Maps';
	const SETTING_PERMISSION_CHECK_UPDATE = 'Check Map Update';
	const SETTING_PERMISSION_SKIP_MAP     = 'Skip Map';
	const SETTING_PERMISSION_RESTART_MAP  = 'Restart Map';
	const SETTING_AUTOSAVE_MAPLIST        = 'Autosave Maplist file';
	const SETTING_MAPLIST_FILE            = 'File to write Maplist in';
	const SETTING_WRITE_OWN_MAPLIST_FILE  = 'Write a own Maplist File for every Server called serverlogin.txt';

	const SEARCH_BY_AUTHOR   = 'Author';
	const SEARCH_BY_MAP_NAME = 'Mapname';


	/*
     * Private properties
	 */

	/** @var MapQueue $mapQueue */
	private $mapQueue = null;

	/** @var MapCommands $mapCommands */
	private $mapCommands = null;

	/** @var MapActions $mapActions */
	private $mapActions = null;

	/** @var MapList $mapList */
	private $mapList = null;

	/** @var DirectoryBrowser $directoryBrowser */
	private $directoryBrowser = null;

	/** @var ManiaExchangeList $mxList */
	private $mxList = null;

	/** @var ManiaExchangeManager $mxManager */
	private $mxManager = null;

	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var Map[] $maps */
	private $maps = array();
	/** @var Map $currentMap */
	private $currentMap = null;
	private $mapEnded   = false;
	private $mapBegan   = false;

	/**
	 * Construct a new map manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		// Children
		$this->mxManager        = new ManiaExchangeManager($this->maniaControl);
		$this->mapList          = new MapList($this->maniaControl);
		$this->directoryBrowser = new DirectoryBrowser($this->maniaControl);
		$this->mxList           = new ManiaExchangeList($this->maniaControl);
		$this->mapCommands      = new MapCommands($maniaControl);
		$this->mapQueue         = new MapQueue($this->maniaControl);
		$this->mapActions       = new MapActions($maniaControl);

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'handleOnInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::AFTERINIT, $this, 'handleAfterInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_MAPLISTMODIFIED, $this, 'mapsModified');

		// Permissions
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_ADD_MAP, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_REMOVE_MAP, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_ERASE_MAP, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_SHUFFLE_MAPS, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_CHECK_UPDATE, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_SKIP_MAP, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_RESTART_MAP, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_AUTOSAVE_MAPLIST, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAPLIST_FILE, "MatchSettings/tracklist.txt");
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WRITE_OWN_MAPLIST_FILE, false);

		//Initlaize Communication Listenings
		$this->initalizeCommunicationListenings();
	}

	/**
	 * Return the map commands
	 *
	 * @return MapCommands
	 */
	public function getMapCommands() {
		return $this->mapCommands;
	}

	/**
	 * Return the map actions
	 *
	 * @return MapActions
	 */
	public function getMapActions() {
		return $this->mapActions;
	}

	/**
	 * Return the map list
	 *
	 * @return MapList
	 */
	public function getMapList() {
		return $this->mapList;
	}

	/**
	 * Return the directory browser
	 *
	 * @return DirectoryBrowser
	 */
	public function getDirectoryBrowser() {
		return $this->directoryBrowser;
	}

	/**
	 * Return the mx list
	 *
	 * @return ManiaExchangeList
	 */
	public function getMXList() {
		return $this->mxList;
	}

	/**
	 * Update a Map from Mania Exchange
	 *
	 * @param Player|null $admin
	 * @param string      $uid
	 */
	public function updateMap($admin, $uid) {
		$this->updateMapTimestamp($uid);

		if (!isset($uid) || !isset($this->maps[$uid])) {
			if ($admin) {
				$message = $this->maniaControl->getChat()->formatMessage(
					'Error updating Map, unknown UID %s!',
					$uid
				);
				$this->maniaControl->getChat()->sendError($message, $admin);
			}
			return;
		}

		/** @var Map $map */
		$map = $this->maps[$uid];

		$mxId = $map->mx->id;
		$this->removeMap($admin, $uid, true, false);
		if ($admin) {
			$this->addMapFromMx($mxId, $admin->login, true);
		} else {
			$this->addMapFromMx($mxId, null, true);
		}
	}

	/**
	 * Update the Timestamp of a Map
	 *
	 * @param string $uid
	 * @return bool
	 */
	public function updateMapTimestamp($uid) {
		$mysqli       = $this->maniaControl->getDatabase()->getMysqli();
		//TODO mxid was set to 0, verify what for
		$mapQuery     = "UPDATE `" . self::TABLE_MAPS . "` SET
				`changed` = NOW()
				WHERE `uid` LIKE ?";

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
	 * Remove a Map
	 *
	 * @param Player|null $admin
	 * @param string      $uid
	 * @param bool        $eraseFile
	 * @param bool        $message
	 * @return bool
	 */
	public function removeMap($admin, $uid, $eraseFile = false, $message = true) {
		if (!isset($this->maps[$uid])) {
			if ($admin) {
				$this->maniaControl->getChat()->sendError('Map does not exist!', $admin);
			}
			return false;
		}

		/** @var Map $map */
		$map = $this->maps[$uid];

		// Unset the Map everywhere
		$this->getMapQueue()->removeFromMapQueue($admin, $map->uid);

		if ($map->mx) {
			$this->getMXManager()->unsetMap($map->mx->id);
		}

		// Remove map
		try {
			$this->maniaControl->getClient()->removeMap($map->fileName);
		} catch (NotInListException $e) {
		} catch (FileException $e) {
		}

		unset($this->maps[$uid]);

		if ($eraseFile) {
			// Check if ManiaControl can even write to the maps dir
			$mapDir = $this->maniaControl->getClient()->getMapsDirectory();
			if ($this->maniaControl->getServer()->checkAccess($mapDir)) {
				$mapFile = $mapDir . $map->fileName;

				// Delete map file
				if (!@unlink($mapFile)) {
					if ($admin) {
						$message = $this->maniaControl->getChat()->formatMessage(
							'Could not erase the map file %s.',
							$mapFile
						);
						$this->maniaControl->getChat()->sendError($message, $admin);
					}
					$eraseFile = false;
				}
			} else {
				if ($admin) {
					$this->maniaControl->getChat()->sendError('Could not erase the map file (no access to Maps-Directory).', $admin);
				}
				$eraseFile = false;
			}
		}

		// Show Message
		if ($message) {
			$action = ($eraseFile ? 'erased' : 'removed');
			if ($admin) {
				$message = $this->maniaControl->getChat()->formatMessage(
					"%s {$action} %s!",
					$admin,
					$map
				);
			} else {
				$message = $this->maniaControl->getChat()->formatMessage(
					"%s got {$action}!",
					$map
				);
			}
			$this->maniaControl->getChat()->sendSuccess($message);
			Logger::logInfo($message, true);
		}

		return true;
	}

	/**
	 * Return the map queue
	 *
	 * @return MapQueue
	 */
	public function getMapQueue() {
		return $this->mapQueue;
	}

	/**
	 * Return the mx manager
	 *
	 * @return ManiaExchangeManager
	 */
	public function getMXManager() {
		return $this->mxManager;
	}

	/**
	 * Adds a Map from Mania Exchange
	 *
	 * @param int    $mapId
	 * @param string $login
	 * @param bool   $update
	 * @param bool   $displayMessage
	 */
	public function addMapFromMx($mapId, $login, $update = false) {
		if (is_numeric($mapId)) {
			// Check if map exists
			$this->maniaControl->getMapManager()->getMXManager()->fetchMapInfo($mapId, function (MXMapInfo $mapInfo = null) use (
				&$login, &$update
			) {
				if (!$mapInfo || !isset($mapInfo->uploaded)) {
					if ($login) {
						// Invalid id
						$this->maniaControl->getChat()->sendError('Invalid MX-Id!', $login);
					}
					return;
				}

				$url = $mapInfo->downloadurl;

				if ($key = $this->maniaControl->getSettingManager()->getSettingValue($this->getMXManager(), ManiaExchangeManager::SETTING_MX_KEY)) {
					$url .= "?key=" . $key;
				}

				$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $url);
				$asyncHttpRequest->setContentType(AsyncHttpRequest::CONTENT_TYPE_UTF8);
				$asyncHttpRequest->setHeaders(array("X-ManiaPlanet-ServerLogin: " . $this->maniaControl->getServer()->login));
				$asyncHttpRequest->setCallable(function ($file, $error) use (
					&$login, &$mapInfo, &$update
				) {
					if (!$file || $error) {
						if ($login) {
							// Download error
							$this->maniaControl->getChat()->sendError("Download failed: {$error}!", $login);
						}
						return;
					}
					$this->processMapFile($file, $mapInfo, $login, $update);
				});

				$asyncHttpRequest->getData();
			});
		}
		return;
	}

	/**
	 * Process the MapFile
	 *
	 * @param string    $file
	 * @param MXMapInfo $mapInfo
	 * @param string    $login
	 * @param bool      $update
	 * @throws InvalidArgumentException
	 */
	private function processMapFile($file, MXMapInfo $mapInfo, $login, $update) {
		// Check if map is already on the server
		if ($this->getMapByUid($mapInfo->uid)) {
			$this->maniaControl->getChat()->sendError('Map is already on the server!', $login);
			return;
		}

		// Save map
		$fileName = $mapInfo->id . '_' . $mapInfo->name . '.Map.Gbx';
		$fileName = FileUtil::getClearedFileName($fileName);

		$downloadFolderName  = $this->maniaControl->getSettingManager()->getSettingValue($this, 'MapDownloadDirectory', 'MX');
		$relativeMapFileName = $downloadFolderName . DIRECTORY_SEPARATOR . $fileName;
		$mapDir              = $this->maniaControl->getServer()->getDirectory()->getMapsFolder();
		$downloadDirectory   = $mapDir . $downloadFolderName . DIRECTORY_SEPARATOR;
		$fullMapFileName     = $downloadDirectory . $fileName;

		// Check if it can get written locally
		if ($this->maniaControl->getServer()->checkAccess($mapDir)) {
			// Create download directory if necessary
			if (!is_dir($downloadDirectory) && !mkdir($downloadDirectory) || !is_writable($downloadDirectory)) {
				$message = $this->maniaControl->getChat()->formatMessage(
					'ManiaControl does not have to rights to save maps in %s!',
					$downloadDirectory
				);
				$this->maniaControl->getChat()->sendError($message, $login);
				return;
			}

			if (!@file_put_contents($fullMapFileName, $file)) {
				// Save error
				$this->maniaControl->getChat()->sendError('Saving map failed!', $login);
				return;
			}
		} else {
			// Write map via write file method
			try {
				$this->maniaControl->getClient()->writeFile($relativeMapFileName, $file);
			} catch (InvalidArgumentException $e) {
				if ($e->getMessage() === 'data are too big') {
					$this->maniaControl->getChat()->sendError('Map is too big for a remote save!', $login);
					return;
				}
				throw $e;
			} catch (FileException $e) {
				$this->maniaControl->getChat()->sendError('Could not write file!', $login);
				return;
			}
		}

		// Check for valid map
		try {
			$this->maniaControl->getClient()->checkMapForCurrentServerParams($relativeMapFileName);
		} catch (InvalidMapException $e) {
			$this->maniaControl->getChat()->sendException($e, $login);
			return;
		} catch (FileException $e) {
			$this->maniaControl->getChat()->sendException($e, $login);
			return;
		}catch (FaultException $e){
			$this->maniaControl->getChat()->sendException($e, $login);
			return;
		}

		// Add map to map list
		try {
			$this->maniaControl->getClient()->insertMap($relativeMapFileName);
		} catch (AlreadyInListException $e) {
			$this->maniaControl->getChat()->sendException($e, $login);
			return;
		} catch (InvalidMapException $e) {
			$this->maniaControl->getChat()->sendException($e, $login);
			if ($e->getMessage() != 'Map lightmap is not up to date. (will still load for now)') {
				return;
			}
		}

		$this->updateFullMapList();

		// Update Mx MapInfo
		$this->maniaControl->getMapManager()->getMXManager()->updateMapObjectsWithManiaExchangeIds(array($mapInfo));

		// Update last updated time
		$map = $this->getMapByUid($mapInfo->uid);

		if (!$map) {
			// TODO: improve this - error reports about not existing maps
			$this->maniaControl->getErrorHandler()->triggerDebugNotice('Map not in List after Insert!');
			$this->maniaControl->getChat()->sendError('Server Error!', $login);
			return;
		}

		$map->lastUpdate = time();
		//Update TimeStamp in Database
		$this->updateMapTimestamp($mapInfo->uid);


		//TODO messages for communication
		if ($login) {
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
			if (!$update) {
				$message = $this->maniaControl->getChat()->formatMessage(
					'%s added %s from MX!',
					$player,
					$map
				);
				$this->maniaControl->getChat()->sendSuccess($message);
				Logger::logInfo($message, true);
				
				$this->maniaControl->getMapManager()->getMapQueue()->addMapToMapQueue($login, $mapInfo->uid);
			} else {
				$message = $this->maniaControl->getChat()->formatMessage(
					'%s updated %s from MX!',
					$player,
					$map
				);
				$this->maniaControl->getChat()->sendSuccess($message);
				Logger::logInfo($message, true);
			}
		}
	}

	/**
	 * Get Map by UID
	 *
	 * @param string $uid
	 * @return Map
	 */
	public function getMapByUid($uid) {
		if (isset($this->maps[$uid])) {
			return $this->maps[$uid];
		}
		return null;
	}

	/**
	 * Updates the full Map list, needed on Init, addMap and on ShuffleMaps
	 */
	private function updateFullMapList() {
		$tempList = array();

		try {
			$offset = 0;
			while ($this->maniaControl->getClient() && $offset < 5000) {
				$maps = $this->maniaControl->getClient()->getMapList(150, $offset);

				foreach ($maps as $rpcMap) {
					if (array_key_exists($rpcMap->uId, $this->maps)) {
						// Map already exists, only update index
						$tempList[$rpcMap->uId] = $this->maps[$rpcMap->uId];
					} else {
						// Insert Map Object
						$map                 = $this->initializeMap($rpcMap);
						$tempList[$map->uid] = $map;
					}
				}

				$offset += 150;
			}

		} catch (IndexOutOfBoundException $e) {
		}

		// restore Sorted MapList
		$this->maps = $tempList;

		// Trigger own callback
		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_MAPS_UPDATED);

		// Write MapList
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_AUTOSAVE_MAPLIST)) {
			if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WRITE_OWN_MAPLIST_FILE)) {
				$serverLogin           = $this->maniaControl->getServer()->login;
				$matchSettingsFileName = "MatchSettings/{$serverLogin}.txt";
			} else {
				$matchSettingsFileName = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAPLIST_FILE);
			}

			try {
				$this->maniaControl->getClient()->saveMatchSettings($matchSettingsFileName);
			} catch (FileException $e) {
				Logger::logError("Unable to write the playlist file, please checkout your Matchsetting folder File permissions!");
			}
		}
	}

	/**
	 * Initializes a Map
	 *
	 * @param mixed $rpcMap
	 * @return Map
	 */
	public function initializeMap($rpcMap) {
		$map = new Map($rpcMap);
		$this->saveMap($map);
		return $map;
	}

	/**
	 * Save a Map in the Database
	 *
	 * @param Map $map
	 * @return bool
	 */
	private function saveMap(Map &$map) {
		//TODO saveMaps for whole maplist at once (usage of prepared statements)
		$mysqli   = $this->maniaControl->getDatabase()->getMysqli();
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
		$mapStatement->bind_param('ssssss', $map->uid, $map->rawName, $map->authorLogin, $map->fileName, $map->environment, $map->mapType);
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
	 * Get's a Map by it's Mania-Exchange Id
	 *
	 * @param int $mxId
	 * @return Map
	 */
	public function getMapByMxId($mxId) {
		foreach ($this->maps as $map) {
			if ($map->mx && $map->mx->id == $mxId) {
				return $map;
			}
		}
		return null;
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

		foreach ($shuffledMaps as $map) {
			/** @var Map $map */
			$mapArray[] = $map->fileName;
		}

		try {
			$this->maniaControl->getClient()->chooseNextMapList($mapArray);
		} catch (Exception $e) {
			//TODO temp added 19.04.2014
			$this->maniaControl->getErrorHandler()->triggerDebugNotice('Exception line 331 MapManager' . $e->getMessage());
			trigger_error('Could not shuffle mapList. ' . $e->getMessage());
			return false;
		}

		$this->fetchCurrentMap();

		if ($admin) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'%s shuffled the Maplist!',
				$admin
			);
			$this->maniaControl->getChat()->sendSuccess($message);
			Logger::logInfo($message, true);
		}

		// Restructure if needed
		$this->restructureMapList();
		return true;
	}

	/**
	 * Freshly fetch current Map
	 *
	 * @return Map
	 */
	private function fetchCurrentMap() {
		try {
			$rpcMap = $this->maniaControl->getClient()->getCurrentMapInfo();
		} catch (UnavailableFeatureException $exception) {
			return null;
		}

		if (array_key_exists($rpcMap->uId, $this->maps)) {
			$this->currentMap                = $this->maps[$rpcMap->uId];
			$this->currentMap->authorTime    = $rpcMap->authorTime;
			$this->currentMap->goldTime      = $rpcMap->goldTime;
			$this->currentMap->silverTime    = $rpcMap->silverTime;
			$this->currentMap->bronzeTime    = $rpcMap->bronzeTime;
			$this->currentMap->nbCheckpoints = $rpcMap->nbCheckpoints;
			$this->currentMap->nbLaps        = $rpcMap->nbLaps;
			return $this->currentMap;
		}

		$this->currentMap                   = $this->initializeMap($rpcMap);
		$this->maps[$this->currentMap->uid] = $this->currentMap;
		return $this->currentMap;
	}

	/**
	 * Restructures the Maplist
	 */
	public function restructureMapList() {
		$currentIndex = $this->getMapIndex($this->getCurrentMap());

		// No RestructureNeeded
		if ($currentIndex < Maplist::MAX_MAPS_PER_PAGE - 1) {
			return true;
		}

		$lowerMapArray  = array();
		$higherMapArray = array();

		$index = 0;
		foreach ($this->maps as $map) {
			if ($index < $currentIndex) {
				$lowerMapArray[] = $map->fileName;
			} else {
				$higherMapArray[] = $map->fileName;
			}
			$index++;
		}

		$mapArray = array_merge($higherMapArray, $lowerMapArray);
		array_shift($mapArray);

		try {
			$this->maniaControl->getClient()->chooseNextMapList($mapArray);
		} catch (Exception $e) {
			trigger_error('Error restructuring the Maplist. ' . $e->getMessage());
			return false;
		}
		return true;
	}

	/**
	 * Returns the Map by it's given Array Index
	 *
	 * @param int $index The index starts at 0
	 * @return Map|null
	 */
	public function getMapByIndex($index) {
		$maps = $this->getMaps();
		if ($index > sizeof($maps)) {
			return null;
		} else {
			return $maps[$index];
		}
	}

	/**
	 * Returns the MapIndex of a given map
	 *
	 * @param Map $map
	 * @return int
	 */
	public function getMapIndex(Map $map) {
		$maps = $this->getMaps();
		return array_search($map, $maps);
	}

	/**
	 * Get all Maps
	 *
	 * @param int $offset
	 * @param int $length
	 * @return Map[]
	 */
	public function getMaps($offset = null, $length = null) {
		if ($offset === null) {
			return array_values($this->maps);
		}
		if ($length === null) {
			return array_slice($this->maps, $offset);
		}
		return array_slice($this->maps, $offset, $length);
	}

	/**
	 * Get Current Map
	 *
	 * @return Map
	 */
	public function getCurrentMap() {
		if (!$this->currentMap) {
			return $this->fetchCurrentMap();
		}
		return $this->currentMap;
	}

	/**
	 * Handle OnInit callback
	 */
	public function handleOnInit() {
		$this->updateFullMapList();
		$this->fetchCurrentMap();

		// Restructure Maplist
		$this->restructureMapList();
	}

	/**
	 * Handle AfterInit callback
	 */
	public function handleAfterInit() {
		// Fetch MX infos
		$this->getMXManager()->fetchManiaExchangeMapInformation();
	}

	/**
	 * Handle Script BeginMap callback
	 *
	 * @param string $mapUid
	 * @param string $restart
	 */
	public function handleScriptBeginMap($mapUid, $restart) {
		//TODO remove parseBoolean as soon the mp3 callbacks get removed
		$this->beginMap($mapUid, Formatter::parseBoolean($restart));
	}

	/**
	 * Manage the Begin of a Map
	 *
	 * @param string $uid
	 * @param bool   $restart
	 */
	private function beginMap($uid, $restart = false) {
		//If a restart occurred, first call the endMap to set variables back
		if ($restart) {
			$this->endMap();
		}

		if ($this->mapBegan) {
			return;
		}
		$this->mapBegan = true;
		$this->mapEnded = false;

		if (array_key_exists($uid, $this->maps)) {
			// Map already exists, only update index
			$this->currentMap = $this->maps[$uid];
			if (!$this->currentMap->nbCheckpoints || !$this->currentMap->nbLaps) {
				$rpcMap                          = $this->maniaControl->getClient()->getCurrentMapInfo();
				$this->currentMap->authorTime    = $rpcMap->authorTime;
				$this->currentMap->goldTime      = $rpcMap->goldTime;
				$this->currentMap->silverTime    = $rpcMap->silverTime;
				$this->currentMap->bronzeTime    = $rpcMap->bronzeTime;
				$this->currentMap->nbLaps        = $rpcMap->nbLaps;
				$this->currentMap->nbCheckpoints = $rpcMap->nbCheckpoints;
			}
		}

		// Restructure MapList if id is over 15
		$this->restructureMapList();

		// Update the mx of the map (for update checks, etc.)
		$this->getMXManager()->fetchManiaExchangeMapInformation($this->currentMap);

		// Trigger own BeginMap callback
		$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::BEGINMAP, $this->currentMap);
	}

	/**
	 * Manage the End of a Map
	 */
	private function endMap() {
		if ($this->mapEnded) {
			return;
		}
		$this->mapEnded = true;
		$this->mapBegan = false;

		// Trigger own EndMap callback
		$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDMAP, $this->currentMap);
	}

	/**
	 * Fetch a map by its file path
	 *
	 * @param string $relativeFileName
	 * @return Map
	 */
	public function fetchMapByFileName($relativeFileName) {
		$mapInfo = $this->maniaControl->getClient()->getMapInfo($relativeFileName);
		if (!$mapInfo) {
			return null;
		}
		return $this->initializeMap($mapInfo);
	}

	/**
	 * Handle BeginMap callback
	 *
	 * @param array $callback
	 */
	public function handleBeginMap(array $callback) {
		$this->beginMap($callback[1][0]["UId"]);
	}

	/**
	 * Handle Script EndMap Callback
	 */
	public function handleScriptEndMap() {
		$this->endMap();
	}

	/**
	 * Handle EndMap Callback
	 *
	 * @param array $callback
	 */
	public function handleEndMap(array $callback) {
		$this->endMap();
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
	 * Get the Number of Maps
	 *
	 * @return int
	 */
	public function getMapsCount() {
		return count($this->maps);
	}

	/**
	 * Initializes the Communication Listenings
	 */
	private function initalizeCommunicationListenings() {
		// Communication Listenings
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::GET_CURRENT_MAP, $this, function ($data) {
			return new CommunicationAnswer($this->getCurrentMap());
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::GET_MAP_LIST, $this, function ($data) {
			return new CommunicationAnswer($this->getMaps());
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::GET_MAP, $this, function ($data) {
			if (!is_object($data)) {
				return new CommunicationAnswer("Error in provided Data", true);
			}

			if (property_exists($data, "mxId")) {
				return new CommunicationAnswer($this->getMapByMxId($data->mxId));
			} else if (property_exists($data, "mapUid")) {
				return new CommunicationAnswer($this->getMapByUid($data->mapUid));
			} else {
				return new CommunicationAnswer("No mxId or mapUid provided.", true);
			}
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::ADD_MAP, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "mxId")) {
				return new CommunicationAnswer("No valid mxId provided.", true);
			}

			$this->addMapFromMx($data->mxId, null);

			return new CommunicationAnswer();
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::REMOVE_MAP, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "mapUid")) {
				return new CommunicationAnswer("No valid mapUid provided.", true);
			}

			if (!$this->getMapByUid($data->mapUid)) {
				return new CommunicationAnswer("Map not found.", true);
			}

			$erase = false;
			if (property_exists($data, "eraseMapFile")) {
				$erase = $data->eraseMapFile;
			}
			$showMessage = true;
			if (property_exists($data, "showChatMessage")) {
				$showMessage = $data->showChatMessage;
			}

			$success = $this->removeMap(null, $data->mapUid, $erase, $showMessage);
			return new CommunicationAnswer(array("success" => $success));
		});


		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::UPDATE_MAP, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "mapUid")) {
				return new CommunicationAnswer("No valid mapUid provided.", true);
			}

			$this->updateMap(null, $data->mapUid);
			return new CommunicationAnswer();
		});
	}


	/**
	 * Searches the current map list for an author
	 *
	 * @param $searchString
	 * @return array
	 */
	public function searchMapsByAuthor($searchString) {
		return $this->searchMaps($searchString, self::SEARCH_BY_AUTHOR);
	}


	/**
	 * Searches the current map list for a map name
	 *
	 * @param $searchString
	 * @return array
	 */
	public function searchMapsByMapName($searchString) {
		return $this->searchMaps($searchString, self::SEARCH_BY_MAP_NAME);
	}

	/**
	 * Searches the current map list
	 *
	 * @param        $searchString
	 * @param string $searchBy
	 * @return array
	 */
	private function searchMaps($searchString, $searchBy = self::SEARCH_BY_MAP_NAME) {
		$result       = array();
		$searchString = strtolower($searchString);

		if($searchString == ''){
			return $result;
		}

		foreach ($this->maps as $map) {
			switch ($searchBy) {
				case self::SEARCH_BY_MAP_NAME:
					$mapName = strtolower(Formatter::stripCodes($map->name));

					if (strpos($mapName, $searchString) !== false) {
						array_push($result, $map);
					}
					break;
				case self::SEARCH_BY_AUTHOR:
					if (strpos(strtolower($map->authorLogin), $searchString) !== false) {
						array_push($result, $map);
					}
			}
		}
		return $result;
	}


	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
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
}
