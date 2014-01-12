<?php

namespace ManiaControl\Maps;

use ManiaControl\ManiaControl;

/**
 * Mania Exchange Info Searcher Class
 *
 * @author steeffeen & kremsy
 */
class ManiaExchangeInfoSearcher { //TODO rename to ManiaExchangeManager
	/**
	 * Constants
	 */
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
	const MAPS_PER_MX_FETCH               = 50;

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
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}
		foreach($mxMapInfos as $mxMapInfo) {
			/** @var MXMapInfo $mxMapInfo */
			$saveMapStatement->bind_param('is', $mxMapInfo->id, $mxMapInfo->uid);
			$saveMapStatement->execute();
			if($saveMapStatement->error) {
				trigger_error($saveMapStatement->error);
			}

			//Take the uid out of the vektor
			if(isset($this->mxIdUidVector[$mxMapInfo->id])) {
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
	 * Fetch Map Information from Mania Exchange
	 */
	public function fetchManiaExchangeMapInformations() {
		$maps        = $this->maniaControl->mapManager->getMaps();
		$mysqli      = $this->maniaControl->database->mysqli;
		$mapIdString = '';

		// Fetch mx ids
		$fetchMapQuery     = "SELECT `mxid`, `changed`  FROM `" . MapManager::TABLE_MAPS . "`
				WHERE `index` = ?;";
		$fetchMapStatement = $mysqli->prepare($fetchMapQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}

		$id = 0;
		foreach($maps as $map) {
			/** @var Map $map */
			$fetchMapStatement->bind_param('i', $map->index);
			$fetchMapStatement->execute();
			if($fetchMapStatement->error) {
				trigger_error($fetchMapStatement->error);
				continue;
			}
			$fetchMapStatement->store_result();
			$fetchMapStatement->bind_result($mxId, $changed);
			$fetchMapStatement->fetch();
			$fetchMapStatement->free_result();

			//Set changed time into the map object
			$map->lastUpdate = strtotime($changed);

			if($mxId != 0) {
				$appendString = $mxId . ',';
				//Set the mx id to the mxidmapvektor
				$this->mxIdUidVector[$mxId] = $map->uid;
			} else {
				$appendString = $map->uid . ',';
			}

			$id++;

			//If Max Maplimit is reached, or string gets too long send the request
			if($id % self::MAPS_PER_MX_FETCH == 0) {
				$maps = $this->getMaplistByMixedUidIdString($mapIdString);
				$this->updateMapObjectsWithManiaExchangeIds($maps);
				$mapIdString = '';
			}

			$mapIdString .= $appendString;
		}

		if($mapIdString != '') {
			$maps = $this->getMaplistByMixedUidIdString($mapIdString);
			$this->updateMapObjectsWithManiaExchangeIds($maps);
		}

		$fetchMapStatement->close();
	}


	public function getMaplistByMixedUidIdString($string) {
		// Get Title Id
		$titleId     = $this->maniaControl->server->titleId;
		$titlePrefix = strtolower(substr($titleId, 0, 2));

		// compile search URL
		$url = 'http://api.mania-exchange.com/' . $titlePrefix . '/maps/?ids=' . $string;

		// $mapInfo = FileUtil::loadFile($url, "application/json"); //TODO use mc fileutil
		$mapInfo = $this->get_file($url);

		// TODO errors
		/*
		 * if ($file === false) { $this->error = 'Connection or response error on ' . $url; return array(); } elseif ($file === -1) { $this->error =
		 * 'Timed out while reading data from ' . $url; return array(); } elseif ($file == '') { if (empty($maps)) { $this->error = 'No data returned
		 * from ' . $url; return array(); } else { break; } }
		 */

		$mxMapList = json_decode($mapInfo);
		if($mxMapList === null) {
			trigger_error('Cannot decode searched JSON data from ' . $url);
			return null;
		}

		$maps = array();
		foreach($mxMapList as $map) {
			if(!empty($map)) {
				array_push($maps, new MXMapInfo($titlePrefix, $map));
			}
		}
		return $maps;
	}

	/**
	 * Gets a Maplist from Mania Exchange
	 *
	 * @param string $name
	 * @param string $author
	 * @param string $env
	 * @param int    $maxMapsReturned
	 * @param int    $searchOrder
	 * @return array null
	 */
	public function getMaps($name = '', $author = '', $env = '', $maxMapsReturned = 100, $searchOrder = self::SEARCH_ORDER_UPDATED_NEWEST) {
		// Get Title Id
		$titleId     = $this->maniaControl->server->titleId;
		$titlePrefix = strtolower(substr($titleId, 0, 2));

		// Get MapTypes
		$this->maniaControl->client->query('GetModeScriptInfo');
		$scriptInfos = $this->maniaControl->client->getResponse();

		$mapTypes = $scriptInfos["CompatibleMapTypes"];

		// compile search URL
		$url = 'http://' . $titlePrefix . '.mania-exchange.com/tracksearch?api=on';

		if($env != '') {
			$url .= '&environments=' . $this->getEnvironment($env);
		}
		if($name != '') {
			$url .= '&trackname=' . str_replace(" ", "%20", $name);
		}
		if($author != '') {
			$url .= '&author=' . $author;
		}

		$url .= '&priord=' . $searchOrder;
		$url .= '&limit=' . $maxMapsReturned;
		$url .= '&mtype=' . $mapTypes;

		// $mapInfo = FileUtil::loadFile($url, "application/json"); //TODO use mc fileutil
		$mapInfo = $this->get_file($url);

		// TODO errors
		/*
		 * if ($file === false) { $this->error = 'Connection or response error on ' . $url; return array(); } elseif ($file === -1) { $this->error =
		 * 'Timed out while reading data from ' . $url; return array(); } elseif ($file == '') { if (empty($maps)) { $this->error = 'No data returned
		 * from ' . $url; return array(); } else { break; } }
		 */

		$mxMapList = json_decode($mapInfo);
		if($mxMapList === null) {
			trigger_error('Cannot decode searched JSON data from ' . $url);
			return null;
		}

		$maps = array();
		foreach($mxMapList as $map) {
			if(!empty($map)) {
				array_push($maps, new MXMapInfo($titlePrefix, $map));
			}
		}
		return $maps;
	}

	private function get_file($url) {
		$url   = parse_url($url);
		$port  = isset($url['port']) ? $url['port'] : 80;
		$query = isset($url['query']) ? "?" . $url['query'] : "";

		$fp = @fsockopen($url['host'], $port, $errno, $errstr, 4);
		if(!$fp) {
			return false;
		}

		fwrite($fp, 'GET ' . $url['path'] . $query . " HTTP/1.0\r\n" . 'Host: ' . $url['host'] . "\r\n" . 'Content-Type: application/json' . "\r\n" . 'User-Agent: ManiaControl v' . ManiaControl::VERSION . "\r\n\r\n");
		stream_set_timeout($fp, 2);
		$res               = '';
		$info['timed_out'] = false;
		while(!feof($fp) && !$info['timed_out']) {
			$res .= fread($fp, 512);
			$info = stream_get_meta_data($fp);
		}
		fclose($fp);

		if($info['timed_out']) {
			return -1;
		} else {
			if(substr($res, 9, 3) != '200') {
				return false;
			}
			$page = explode("\r\n\r\n", $res, 2);
			return trim($page[1]);
		}
	} // get_file
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

//TODO put in own file
class MXMapInfo {
	public $prefix, $id, $uid, $name, $userid, $author, $uploaded, $updated, $type, $maptype;
	public $titlepack, $style, $envir, $mood, $dispcost, $lightmap, $modname, $exever;
	public $exebld, $routes, $length, $unlimiter, $laps, $diffic, $lbrating, $trkvalue;
	public $replaytyp, $replayid, $replaycnt, $acomment, $awards, $comments, $rating;
	public $ratingex, $ratingcnt, $pageurl, $replayurl, $imageurl, $thumburl, $dloadurl;

	/**
	 * Returns map object with all available data from MX map data
	 *
	 * @param String $prefix MX URL prefix
	 * @param Object $map    The MX map data from MXInfoSearcher
	 * @return MXMapInfo
	 */
	public function __construct($prefix, $mx) {
		$this->prefix = $prefix;
		if($mx) {
			if($this->prefix == 'tm') {
				$dir = 'tracks';
			} else // 'sm' || 'qm'
			{
				$dir = 'maps';
			}

			if($this->prefix == 'tm' || !property_exists($mx, "MapID")) {
				$this->id = $mx->TrackID;
			} else {
				$this->id = $mx->MapID;
			}

			$this->name = $mx->Name;

			$this->uid       = isset($mx->MapUID) ? $mx->MapUID : '';
			$this->userid    = $mx->UserID;
			$this->author    = $mx->Username;
			$this->uploaded  = $mx->UploadedAt;
			$this->updated   = $mx->UpdatedAt;
			$this->type      = $mx->TypeName;
			$this->maptype   = isset($mx->MapType) ? $mx->MapType : '';
			$this->titlepack = isset($mx->TitlePack) ? $mx->TitlePack : '';
			$this->style     = isset($mx->StyleName) ? $mx->StyleName : '';
			$this->envir     = $mx->EnvironmentName;
			$this->mood      = $mx->Mood;
			$this->dispcost  = $mx->DisplayCost;
			$this->lightmap  = $mx->Lightmap;
			$this->modname   = isset($mx->ModName) ? $mx->ModName : '';
			$this->exever    = $mx->ExeVersion;
			$this->exebld    = $mx->ExeBuild;
			$this->routes    = isset($mx->RouteName) ? $mx->RouteName : '';
			$this->length    = isset($mx->LengthName) ? $mx->LengthName : '';
			$this->unlimiter = isset($mx->UnlimiterRequired) ? $mx->UnlimiterRequired : false;
			$this->laps      = isset($mx->Laps) ? $mx->Laps : 0;
			$this->diffic    = $mx->DifficultyName;
			$this->lbrating  = isset($mx->LBRating) ? $mx->LBRating : 0;
			$this->trkvalue  = isset($mx->TrackValue) ? $mx->TrackValue : 0;
			$this->replaytyp = isset($mx->ReplayTypeName) ? $mx->ReplayTypeName : '';
			$this->replayid  = isset($mx->ReplayWRID) ? $mx->ReplayWRID : 0;
			$this->replaycnt = isset($mx->ReplayCount) ? $mx->ReplayCount : 0;
			$this->acomment  = $mx->Comments;
			$this->awards    = isset($mx->AwardCount) ? $mx->AwardCount : 0;
			$this->comments  = $mx->CommentCount;
			$this->rating    = isset($mx->Rating) ? $mx->Rating : 0.0;
			$this->ratingex  = isset($mx->RatingExact) ? $mx->RatingExact : 0.0;
			$this->ratingcnt = isset($mx->RatingCount) ? $mx->RatingCount : 0;

			if($this->trkvalue == 0 && $this->lbrating > 0) {
				$this->trkvalue = $this->lbrating;
			} elseif($this->lbrating == 0 && $this->trkvalue > 0) {
				$this->lbrating = $this->trkvalue;
			}

			$search         = array(chr(31), '[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]', '[url]', '[/url]');
			$replace        = array('<br/>', '<b>', '</b>', '<i>', '</i>', '<u>', '</u>', '<i>', '</i>');
			$this->acomment = str_ireplace($search, $replace, $this->acomment);
			$this->acomment = preg_replace('/\[url=.*\]/', '<i>', $this->acomment);

			$this->pageurl  = 'http://' . $this->prefix . '.mania-exchange.com/' . $dir . '/view/' . $this->id;
			$this->imageurl = 'http://' . $this->prefix . '.mania-exchange.com/' . $dir . '/screenshot/normal/' . $this->id;
			$this->thumburl = 'http://' . $this->prefix . '.mania-exchange.com/' . $dir . '/screenshot/small/' . $this->id;
			$this->dloadurl = 'http://' . $this->prefix . '.mania-exchange.com/' . $dir . '/download/' . $this->id;

			if($this->prefix == 'tm' && $this->replayid > 0) {
				$this->replayurl = 'http://' . $this->prefix . '.mania-exchange.com/replays/download/' . $this->replayid;
			} else {
				$this->replayurl = '';
			}
		}
	} // MXMapInfo
} // class MXMapInfo


