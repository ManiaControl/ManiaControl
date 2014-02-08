<?php

namespace ManiaControl\ManiaExchange;

use ManiaControl\ManiaControl;
use ManiaControl\Maps\Map;
use ManiaControl\Maps\MapManager;

/**
 * Mania Exchange Info Searcher Class
 *
 * @author steeffeen & kremsy
 */
class ManiaExchangeManager {
	/**
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
	const SEARCH_ORDER_LENGHT_SHORTEST    = 14;
	const SEARCH_ORDER_LENGHT_LONGEST     = 15;

	//Maximum Maps per request
	const MAPS_PER_MX_FETCH = 50;

	/**
	 * Private Propertieswc
	 */
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
	 * Store Map Info from MX and store the mxid in the database and the mx info in the map object
	 *
	 * @param $mxMapInfos
	 */
	public function updateMapObjectsWithManiaExchangeIds($mxMapInfos) {
		$mysqli = $this->maniaControl->database->mysqli;
		// Save map data
		$saveMapQuery     = "UPDATE `" . MapManager::TABLE_MAPS . "`
				SET `mxid` = ?
				WHERE `uid` = ?;";
		$saveMapStatement = $mysqli->prepare($saveMapQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}
		foreach($mxMapInfos as $mxMapInfo) {
			/** @var MXMapInfo $mxMapInfo */
			$saveMapStatement->bind_param('is', $mxMapInfo->id, $mxMapInfo->uid);
			$saveMapStatement->execute();
			if ($saveMapStatement->error) {
				trigger_error($saveMapStatement->error);
			}

			//Take the uid out of the vektor
			if (isset($this->mxIdUidVector[$mxMapInfo->id])) {
				$uid = $this->mxIdUidVector[$mxMapInfo->id];
			} else {
				$uid = $mxMapInfo->uid;
			}
			$map = $this->maniaControl->mapManager->getMapByUid($uid);

			/** @var Map $map */
			$map->mx = $mxMapInfo;
		}
		$saveMapStatement->close();
	}

	/**
	 * Unset Map by Mx Id
	 *
	 * @param $mxId
	 */
	public function unsetMap($mxId) {
		unset($this->mxIdUidVector[$mxId]);
	}

	/**
	 * Fetch Map Information from Mania Exchange
	 *
	 * @param null $map
	 */
	public function fetchManiaExchangeMapInformations($map = null) {
		if (!$map) {
			//Fetch Informations for whole Maplist
			$maps = $this->maniaControl->mapManager->getMaps();
		} else {
			//Fetch Information for a single map
			$maps[] = $map;
		}

		$mysqli      = $this->maniaControl->database->mysqli;
		$mapIdString = '';

		// Fetch mx ids
		$fetchMapQuery     = "SELECT `mxid`, `changed`  FROM `" . MapManager::TABLE_MAPS . "`
				WHERE `index` = ?;";
		$fetchMapStatement = $mysqli->prepare($fetchMapQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}

		$id = 0;
		foreach($maps as $map) {
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

			if ($mxId != 0) {
				$appendString = $mxId . ',';
				//Set the mx id to the mxidmapvektor
				$this->mxIdUidVector[$mxId] = $map->uid;
			} else {
				$appendString = $map->uid . ',';
			}

			$id++;

			//If Max Maplimit is reached, or string gets too long send the request
			if ($id % self::MAPS_PER_MX_FETCH == 0) {
				$mapIdString = substr($mapIdString, 0, -1);
				$this->getMaplistByMixedUidIdString($mapIdString);
				$mapIdString = '';
			}

			$mapIdString .= $appendString;
		}

		if ($mapIdString != '') {
			$mapIdString = substr($mapIdString, 0, -1);
			$this->getMaplistByMixedUidIdString($mapIdString);
		}

		$fetchMapStatement->close();
	}

	/**
	 * Get Map Info Asynchronously
	 *
	 * @param $id
	 * @param $function
	 * @return bool
	 */
	public function getMapInfo($id, $function) {
		// Get Title Id
		$titleId     = $this->maniaControl->server->titleId;
		$titlePrefix = strtolower(substr($titleId, 0, 2));

		// compile search URL
		$url = 'http://api.mania-exchange.com/' . $titlePrefix . '/maps/?ids=' . $id;

		return $this->maniaControl->fileReader->loadFile($url, function ($mapInfo, $error) use (&$function, $titlePrefix, $url) {
			$mxMapInfo = null;
			if ($error) {
				trigger_error($error);
			} else {
				$mxMapList = json_decode($mapInfo);
				if ($mxMapList === null) {
					trigger_error('Cannot decode searched JSON data from ' . $url);
				} else {
					$mxMapInfo = new MXMapInfo($titlePrefix, $mxMapList[0]);
				}
			}
			call_user_func($function, $mxMapInfo);
		}, "application/json");
	}


	/**
	 * Get the Whole MapList from MX by Mixed Uid and Id String fetch
	 *
	 * @param $string
	 * @return array|null
	 */
	public function getMaplistByMixedUidIdString($string) {
		// Get Title Id
		$titleId     = $this->maniaControl->server->titleId;
		$titlePrefix = strtolower(substr($titleId, 0, 2));

		// compile search URL
		$url = 'http://api.mania-exchange.com/' . $titlePrefix . '/maps/?ids=' . $string;

		$success = $this->maniaControl->fileReader->loadFile($url, function ($mapInfo, $error) use ($titlePrefix, $url) {
			if ($error) {
				trigger_error($error . " " . $url);
				return null;
			}

			$mxMapList = json_decode($mapInfo);
			if ($mxMapList === null) {
				trigger_error('Cannot decode searched JSON data from ' . $url);
				return null;
			}

			$maps = array();
			foreach($mxMapList as $map) {
				if (!empty($map)) {
					array_push($maps, new MXMapInfo($titlePrefix, $map));
				}
			}

			$this->updateMapObjectsWithManiaExchangeIds($maps);
			return true;
		}, "application/json");

		return $success;
	}

	/**
	 * Fetch a MapList Asynchronously
	 *
	 * @param        $function
	 * @param string $name
	 * @param string $author
	 * @param string $env
	 * @param int    $maxMapsReturned
	 * @param int    $searchOrder
	 * @return bool
	 */
	public function getMapsAsync($function, $name = '', $author = '', $env = '', $maxMapsReturned = 100, $searchOrder = self::SEARCH_ORDER_UPDATED_NEWEST) {
		if (!is_callable($function)) {
			$this->maniaControl->log("Function is not callable");
			return false;
		}

		// Get Title Id
		$titleId     = $this->maniaControl->server->titleId;
		$titlePrefix = strtolower(substr($titleId, 0, 2));

		// Get MapTypes
		$scriptInfos = $this->maniaControl->client->getModeScriptInfo();

		$mapTypes = $scriptInfos->compatibleMapTypes;

		// compile search URL
		$url = 'http://' . $titlePrefix . '.mania-exchange.com/tracksearch?api=on';

		if ($env != '') {
			$url .= '&environments=' . $this->getEnvironment($env);
		}
		if ($name != '') {
			$url .= '&trackname=' . str_replace(" ", "%20", $name);
		}
		if ($author != '') {
			$url .= '&author=' . $author;
		}

		$url .= '&priord=' . $searchOrder;
		$url .= '&limit=' . $maxMapsReturned;
		$url .= '&mtype=' . $mapTypes;


		$fileFunc = function ($mapInfo, $error) use (&$function, $titlePrefix) {
			if ($error) {
				trigger_error($error);
				return null;
			}

			$mxMapList = json_decode($mapInfo);
			if ($mxMapList === null) {
				trigger_error('Cannot decode searched JSON data');
				return null;
			}

			$maps = array();
			foreach($mxMapList as $map) {
				if (!empty($map)) {
					array_push($maps, new MXMapInfo($titlePrefix, $map));
				}
			}

			call_user_func($function, $maps);

			return true;
		};

		$success = $this->maniaControl->fileReader->loadFile($url, $fileFunc, "application/json");

		return $success;
	}

	/**
	 * Gets the Current Environemnt by String
	 *
	 * @param $env
	 * @return int
	 */
	private function getEnvironment($env) {
		switch($env) {
			case 'TMCanyon':
			case 'SMStorm':
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