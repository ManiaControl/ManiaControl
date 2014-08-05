<?php

namespace ManiaControl\ManiaExchange;

use ManiaControl\Files\AsynchronousFileReader;
use ManiaControl\ManiaControl;
use ManiaControl\Maps\Map;
use ManiaControl\Maps\MapManager;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Mania Exchange Info Searcher Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaExchangeManager {
	/*
	 * Constants
	 */
	//Search others
	const SEARCH_ORDER_NONE               = -1;
	const SEARCH_ORDER_TRACK_NAME         = 0;
	const SEARCH_ORDER_AUTHOR             = 1;
	const SEARCH_ORDER_UPLOADED_NEWEST    = 2;
	const SEARCH_ORDER_UPLOADED_OLDEST    = 3;
	const SEARCH_ORDER_UPDATED_NEWEST     = 4;
	const SEARCH_ORDER_UPDATED_OLDEST     = 5;
	const SEARCH_ORDER_ACTIVITY_LATEST    = 6;
	const SEARCH_ORDER_ACTIVITY_OLDEST    = 7;
	const SEARCH_ORDER_AWARDS_MOST        = 8;
	const SEARCH_ORDER_AWARDS_LEAST       = 9;
	const SEARCH_ORDER_COMMENTS_MOST      = 10;
	const SEARCH_ORDER_COMMENTS_LEAST     = 11;
	const SEARCH_ORDER_DIFFICULTY_EASIEST = 12;
	const SEARCH_ORDER_DIFFICULTY_HARDEST = 13;
	const SEARCH_ORDER_LENGTH_SHORTEST    = 14;
	const SEARCH_ORDER_LENGTH_LONGEST     = 15;

	//Maximum Maps per request
	const MAPS_PER_MX_FETCH = 50;

	const MIN_EXE_BUILD = "2014-04-01_00_00";

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $mxIdUidVector = array();

	/**
	 * Construct map manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Unset Map by Mx Id
	 *
	 * @param int $mxId
	 */
	public function unsetMap($mxId) {
		if (isset($this->mxIdUidVector[$mxId])) {
			unset($this->mxIdUidVector[$mxId]);
		}
	}

	/**
	 * Fetch Map Information from Mania Exchange
	 *
	 * @param mixed $maps
	 */
	public function fetchManiaExchangeMapInformation($maps = null) {
		if ($maps) {
			// Fetch Information for a single map
			$maps = array($maps);
		} else {
			// Fetch Information for whole MapList
			$maps = $this->maniaControl->getMapManager()
			                           ->getMaps();
		}

		$mysqli      = $this->maniaControl->getDatabase()
		                                  ->getMysqli();
		$mapIdString = '';

		// Fetch mx ids
		$fetchMapQuery     = "SELECT `mxid`, `changed`  FROM `" . MapManager::TABLE_MAPS . "`
				WHERE `index` = ?;";
		$fetchMapStatement = $mysqli->prepare($fetchMapQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}

		$index = 0;
		foreach ($maps as $map) {
			if (!$map) {
				// TODO: remove after resolving of error report about "non-object"
				$this->maniaControl->getErrorHandler()
				                   ->triggerDebugNotice('Non-Object-Map', $map, $maps);
				continue;
			}
			/** @var Map $map */
			$fetchMapStatement->bind_param('i', $map->index);
			$fetchMapStatement->execute();
			if ($fetchMapStatement->error) {
				trigger_error($fetchMapStatement->error);
				continue;
			}
			$fetchMapStatement->store_result();
			$fetchMapStatement->bind_result($mxId, $changed);
			$fetchMapStatement->fetch();
			$fetchMapStatement->free_result();

			//Set changed time into the map object
			$map->lastUpdate = strtotime($changed);

			if ($mxId) {
				$appendString = $mxId . ',';
				//Set the mx id to the mxidmapvektor
				$this->mxIdUidVector[$mxId] = $map->uid;
			} else {
				$appendString = $map->uid . ',';
			}

			$index++;

			//If Max Maplimit is reached, or string gets too long send the request
			if ($index % self::MAPS_PER_MX_FETCH === 0) {
				$mapIdString = substr($mapIdString, 0, -1);
				$this->fetchMaplistByMixedUidIdString($mapIdString);
				$mapIdString = '';
			}

			$mapIdString .= $appendString;
		}

		if ($mapIdString) {
			$mapIdString = substr($mapIdString, 0, -1);
			$this->fetchMaplistByMixedUidIdString($mapIdString);
		}

		$fetchMapStatement->close();
	}

	/**
	 * Fetch the whole Map List from MX via mixed Uid and Id Strings
	 *
	 * @param string $string
	 */
	public function fetchMaplistByMixedUidIdString($string) {
		// Get Title Prefix
		$titlePrefix = $this->maniaControl->getMapManager()
		                                  ->getCurrentMap()
		                                  ->getGame();

		// compile search URL
		$url = "http://api.mania-exchange.com/{$titlePrefix}/maps/?ids={$string}";

		$this->maniaControl->getFileReader()
		                   ->loadFile($url, function ($mapInfo, $error) use ($titlePrefix, $url) {
			if ($error) {
				trigger_error("Error: '{$error}' for Url '{$url}'");
				return;
			}
			if (!$mapInfo) {
				return;
			}

			$mxMapList = json_decode($mapInfo);
			if ($mxMapList === null) {
				trigger_error("Can't decode searched JSON Data from Url '{$url}'");
				return;
			}

			$maps = array();
			foreach ($mxMapList as $map) {
				if ($map) {
					$mxMapObject = new MXMapInfo($titlePrefix, $map);
					if ($mxMapObject) {
						array_push($maps, $mxMapObject);
					}
				}
			}

			$this->updateMapObjectsWithManiaExchangeIds($maps);
		}, AsynchronousFileReader::CONTENT_TYPE_JSON);
	}

	/**
	 * Store MX Map Info in the Database and the MX Info in the Map Object
	 *
	 * @param array $mxMapInfos
	 */
	public function updateMapObjectsWithManiaExchangeIds(array $mxMapInfos) {
		$mysqli = $this->maniaControl->getDatabase()
		                             ->getMysqli();
		// Save map data
		$saveMapQuery     = "UPDATE `" . MapManager::TABLE_MAPS . "`
				SET `mxid` = ?
				WHERE `uid` = ?;";
		$saveMapStatement = $mysqli->prepare($saveMapQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}
		$saveMapStatement->bind_param('is', $mapMxId, $mapUId);
		foreach ($mxMapInfos as $mxMapInfo) {
			/** @var MXMapInfo $mxMapInfo */
			$mapMxId = $mxMapInfo->id;
			$mapUId  = $mxMapInfo->uid;
			$saveMapStatement->execute();
			if ($saveMapStatement->error) {
				trigger_error($saveMapStatement->error);
			}

			//Take the uid out of the vector
			if (isset($this->mxIdUidVector[$mxMapInfo->id])) {
				$uid = $this->mxIdUidVector[$mxMapInfo->id];
			} else {
				$uid = $mxMapInfo->uid;
			}
			$map = $this->maniaControl->getMapManager()
			                          ->getMapByUid($uid);
			if ($map) {
				// TODO: how does it come that $map can be empty here? we got an error report for that
				/** @var Map $map */
				$map->mx = $mxMapInfo;
			}
		}
		$saveMapStatement->close();
	}

	/**
	 * @deprecated
	 * @see \ManiaControl\ManiaExchange\ManiaExchangeManager::fetchMaplistByMixedUidIdString()
	 */
	public function getMaplistByMixedUidIdString($string) {
		$this->fetchMaplistByMixedUidIdString($string);
		return true;
	}

	/**
	 * Fetch Map Info asynchronously
	 *
	 * @param int      $mapId
	 * @param callable $function
	 */
	public function fetchMapInfo($mapId, callable $function) {
		// Get Title Prefix
		$titlePrefix = $this->maniaControl->getMapManager()
		                                  ->getCurrentMap()
		                                  ->getGame();

		// compile search URL
		$url = 'http://api.mania-exchange.com/' . $titlePrefix . '/maps/?ids=' . $mapId;

		$this->maniaControl->getFileReader()
		                   ->loadFile($url, function ($mapInfo, $error) use (&$function, $titlePrefix, $url) {
			$mxMapInfo = null;
			if ($error) {
				trigger_error($error);
			} else {
				$mxMapList = json_decode($mapInfo);
				if (!is_array($mxMapList)) {
					trigger_error('Cannot decode searched JSON data from ' . $url);
				} else if (!empty($mxMapList)) {
					$mxMapInfo = new MXMapInfo($titlePrefix, $mxMapList[0]);
				}
			}
			call_user_func($function, $mxMapInfo);
		}, AsynchronousFileReader::CONTENT_TYPE_JSON);
	}

	/**
	 * @deprecated
	 * @see \ManiaControl\ManiaExchange\ManiaExchangeManager::fetchMapsAsync()
	 */
	public function getMapsAsync(callable $function, $name = '', $author = '', $env = '', $maxMapsReturned = 100,
	                             $searchOrder = self::SEARCH_ORDER_UPDATED_NEWEST) {
		$this->fetchMapsAsync($function, $name, $author, $env, $maxMapsReturned, $searchOrder);
		return true;
	}

	/**
	 * Fetch a MapList Asynchronously
	 *
	 * @param callable $function
	 * @param string   $name
	 * @param string   $author
	 * @param string   $env
	 * @param int      $maxMapsReturned
	 * @param int      $searchOrder
	 */
	public function fetchMapsAsync(callable $function, $name = '', $author = '', $env = '', $maxMapsReturned = 100,
	                               $searchOrder = self::SEARCH_ORDER_UPDATED_NEWEST) {
		// TODO: remove $env because it's not really used?

		// Get Title Id
		$titleId     = $this->maniaControl->getServer()->titleId;
		$titlePrefix = $this->maniaControl->getMapManager()
		                                  ->getCurrentMap()
		                                  ->getGame();

		// compile search URL
		$url = 'http://' . $titlePrefix . '.mania-exchange.com/tracksearch2/search?api=on';

		$game      = explode('@', $titleId);
		$envNumber = $this->getEnvironment($game[0]);
		if ($env || $envNumber > -1) {
			$url .= '&environments=' . $envNumber;
		}
		if ($name) {
			$url .= '&trackname=' . str_replace(" ", "%20", $name);
		}
		if ($author) {
			$url .= '&author=' . $author;
		}

		$url .= '&priord=' . $searchOrder;
		$url .= '&limit=' . $maxMapsReturned;

		if ($titlePrefix !== "tm") {
			$url .= '&minexebuild=' . self::MIN_EXE_BUILD;
		}

		// Get MapTypes
		try {
			$scriptInfos = $this->maniaControl->getClient()
			                                  ->getModeScriptInfo();
			$mapTypes    = $scriptInfos->compatibleMapTypes;
			$url .= '&mtype=' . $mapTypes;
		} catch (GameModeException $e) {
		}

		$this->maniaControl->getFileReader()
		                   ->loadFile($url, function ($mapInfo, $error) use (&$function, $titlePrefix) {
			if ($error) {
				trigger_error($error);
				return;
			}

			$mxMapList = json_decode($mapInfo);

			if (!isset($mxMapList->results)) {
				trigger_error('Cannot decode searched JSON data');
				return;
			}

			$mxMapList = $mxMapList->results;

			if ($mxMapList === null) {
				trigger_error('Cannot decode searched JSON data');
				return;
			}

			$maps = array();
			foreach ($mxMapList as $map) {
				if (!empty($map)) {
					array_push($maps, new MXMapInfo($titlePrefix, $map));
				}
			}

			call_user_func($function, $maps);
		}, AsynchronousFileReader::CONTENT_TYPE_JSON);
	}

	/**
	 * Get the Current Environment by String
	 *
	 * @param string $env
	 * @return int
	 */
	private function getEnvironment($env) {
		switch ($env) {
			case 'TMCanyon':
				return 1;
			case 'TMStadium':
				return 2;
			case 'TMValley':
				return 3;
			default:
				return -1;
		}
	}
}
