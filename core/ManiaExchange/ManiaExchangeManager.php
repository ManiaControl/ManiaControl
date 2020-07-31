<?php

namespace ManiaControl\ManiaExchange;

use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Maps\Map;
use ManiaControl\Maps\MapManager;

/**
 * Mania Exchange Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaExchangeManager implements UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 * @deprecated SEARCH Constants
	 */
	//Search orders (prior parameter) https://api.mania-exchange.com/documents/enums#orderings
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

	const SETTING_MX_KEY = "Mania exchange Key";

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl  = null;
	private $mxIdUidVector = array();

	/**
	 * Construct map manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		//$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MX_KEY, "");
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
			$maps = $this->maniaControl->getMapManager()->getMaps();
		}

		$mysqli      = $this->maniaControl->getDatabase()->getMysqli();
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
		foreach ($maps as &$map) {
			if (!$map) {
				// TODO: remove after resolving of error report about "non-object"
				$this->maniaControl->getErrorHandler()->triggerDebugNotice('Non-Object-Map ' . $map->name);
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
		$titlePrefix = $this->maniaControl->getMapManager()->getCurrentMap()->getGame();

		// compile search URL
		$url = "https://api.mania-exchange.com/{$titlePrefix}/maps/?ids={$string}";

		/*if ($key = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MX_KEY)) {
			$url .= "&key=" . $key;
		}*/

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $url);
		$asyncHttpRequest->setContentType(AsyncHttpRequest::CONTENT_TYPE_JSON);
		$asyncHttpRequest->setCallable(function ($mapInfo, $error) use ($titlePrefix, $url) {
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
		});

		$asyncHttpRequest->getData();
	}

	/**
	 * Store MX Map Info in the Database and the MX Info in the Map Object
	 *
	 * @param array $mxMapInfos
	 */
	public function updateMapObjectsWithManiaExchangeIds(array $mxMapInfos) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
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
			$map = $this->maniaControl->getMapManager()->getMapByUid($uid);
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
		$titlePrefix = $this->maniaControl->getMapManager()->getCurrentMap()->getGame();

		// compile search URL
		$url = 'https://api.mania-exchange.com/' . $titlePrefix . '/maps/?ids=' . $mapId;

		/*if ($key = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MX_KEY)) {
			$url .= "&key=" . $key;
		}*/

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $url);
		$asyncHttpRequest->setContentType(AsyncHttpRequest::CONTENT_TYPE_JSON);
		$asyncHttpRequest->setCallable(function ($mapInfo, $error) use (&$function, $titlePrefix, $url) {
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
		});

		$asyncHttpRequest->getData();
	}

	/**
	 * @deprecated
	 * @see \ManiaControl\ManiaExchange\ManiaExchangeMapSearch
	 */
	public function getMapsAsync(callable $function, $name = '', $author = '', $env = '', $maxMapsReturned = 100, $searchOrder = ManiaExchangeMapSearch::SEARCH_ORDER_UPDATED_NEWEST) {
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
	 * @deprecated
	 * @see \ManiaControl\ManiaExchange\ManiaExchangeMapSearch
	 */
	public function fetchMapsAsync(callable $function, $name = '', $author = '', $env = '', $maxMapsReturned = 100, $sortOrder = ManiaExchangeMapSearch::SEARCH_ORDER_UPDATED_NEWEST) {
		$mapSearch = new ManiaExchangeMapSearch($this->maniaControl);
		$mapSearch->setMapName($name);
		$mapSearch->setAuthorName($author);
		$mapSearch->setMapLimit($maxMapsReturned);
		$mapSearch->setPrioritySortOrder($sortOrder);

		if ($env) {
			$mapSearch->setEnvironments($env);
		}

		$mapSearch->fetchMapsAsync($function);
	}
}
