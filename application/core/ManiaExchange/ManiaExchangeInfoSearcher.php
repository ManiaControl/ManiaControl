<?php
namespace ManiaControl\Maps;

use ManiaControl\ManiaControl;

class ManiaExchangeInfoSearcher {
	/**
	 * Constants
	 */
	const SEARCH_ORDER_NONE            = -1;
	const SEARCH_ORDER_TRACK_NAME      = 0;
	const SEARCH_ORDER_AUTHOR          = 1;
	const SEARCH_ORDER_UPLOADED_NEWEST = 2;
	const SEARCH_ORDER_UPLOADED_OLDEST = 3;
	const SEARCH_ORDER_UPDATED_NEWEST  = 4;
	const SEARCH_ORDER_UPDATED_OLDEST  = 5;
	//TODO finish that list
	/*
	 * [19:16] <TGYoshi> 6 => "Activity (Latest) [19:16] <TGYoshi> 7 => "Activity (Oldest) [19:16] <TGYoshi> 8 => "Awards (Most) [19:16] <TGYoshi> 9 => "Awards (Least) [19:16] <TGYoshi> 10 => "Comments (Most) [19:16] <TGYoshi> 11 => "Comments (Least) [19:16] <TGYoshi> 12 => "Difficulty (Easiest) [19:16] <TGYoshi> 13 => "Difficulty (Hardest) [19:16] <TGYoshi> 14 => "Length (Shortest) [19:16] <TGYoshi> 15 => "Length (Longest)
	 */

	/**
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Construct map manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	public function getMaps($maxMapsReturned = 100, $searchOrder = self::SEARCH_ORDER_UPLOADED_NEWEST, $env = '') {
		//Get Title Id
		$titleId = $this->maniaControl->server->titleId;
		$titlePrefix   = strtolower(substr($titleId, 0, 2));

		//Get MapTypes
		$this->maniaControl->client->query('GetModeScriptInfo');
		$scriptInfos = $this->maniaControl->client->getResponse();

		$mapTypes     = $scriptInfos["CompatibleMapTypes"];
		$mapTypeArray = explode(",", $mapTypes);

		var_dump($mapTypes);
		var_dump($mapTypeArray);

		// compile search URL
		$url = 'http://' . $titlePrefix . '.mania-exchange.com/tracksearch?api=on';
		/*	if ($name != '')
				$url .= '&trackname=' . $name;
			if ($author != '')
				$url .= '&author=' . $author;*/

		if($env != '') {
			$url .= '&environments=' . $this->getEnvironment($env);
		}

		$url .= '&priord=' . $searchOrder;
		$url .= '&limit=' . 1; //TODO
		$url .= '&mtype=' . $mapTypeArray[0];

		//	$mapInfo = FileUtil::loadFile($url, "application/json"); //TODO use mp fileutil
		$mapInfo = $this->get_file($url);
		var_dump($url);
		return;
		//TODO errors
		/*if ($file === false) {
				$this->error = 'Connection or response error on ' . $url;
				return array();
			} elseif ($file === -1) {
				$this->error = 'Timed out while reading data from ' . $url;
				return array();
			} elseif ($file == '') {
				if (empty($maps)) {
					$this->error = 'No data returned from ' . $url;
					return array();
				} else {
					break;
				}
			}*/


		$mxMapList = json_decode($mapInfo);
		if($mxMapList === null) {
			trigger_error('Cannot decode searched JSON data from ' . $url);
			return null;
		}


		$maps = array();
		foreach($mxMapList as $map){
			var_dump($map);
			if (!empty($map)) {
				array_push($maps, new MXInfo($titlePrefix, $map));
			}
		}

		var_dump($maps);
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

class MXInfo {

	public $section, $prefix, $id,
		$name, $userid, $author, $uploaded, $updated,
		$type, $maptype, $titlepack, $style, $envir, $mood,
		$dispcost, $lightmap, $modname,
		$exever, $exebld, $routes, $length, $unlimiter, $laps, $diffic,
		$lbrating, $trkvalue, $replaytyp, $replayid, $replaycnt,
		$acomment, $awards, $comments, $rating, $ratingex, $ratingcnt,
		$pageurl, $replayurl, $imageurl, $thumburl, $dloadurl;

	/**
	 * Returns map object with all available data from MX map data
	 *
	 * @param String $prefix
	 *        MX URL prefix
	 * @param Object $map
	 *        The MX map data from MXInfoSearcher
	 * @return MXInfo
	 */
	public function MXInfo($prefix, $mx) {
		$this->prefix   = $prefix;
		if ($mx) {
			if ($this->prefix == 'tm')
				$dir = 'tracks';
			else // 'sm' || 'qm'
				$dir = 'maps';

			//temporary fix
			if($this->prefix == 'tm' || !property_exists($mx, "MapID"))
				$this->id = $mx->TrackID;
			else
				$this->id = $mx->MapID;
			//	$this->id        = ($this->prefix == 'tm') ? $mx->TrackID : $mx->MapID;

			$this->name      = $mx->Name;
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

			if ($this->trkvalue == 0 && $this->lbrating > 0)
				$this->trkvalue = $this->lbrating;
			elseif ($this->lbrating == 0 && $this->trkvalue > 0)
				$this->lbrating = $this->trkvalue;

			$search = array(chr(31), '[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]', '[url]', '[/url]');
			$replace = array('<br/>', '<b>', '</b>', '<i>', '</i>', '<u>', '</u>', '<i>', '</i>');
			$this->acomment  = str_ireplace($search, $replace, $this->acomment);
			$this->acomment  = preg_replace('/\[url=.*\]/', '<i>', $this->acomment);

			$this->pageurl   = 'http://' . $this->prefix . '.mania-exchange.com/' . $dir . '/view/' . $this->id;
			$this->imageurl  = 'http://' . $this->prefix . '.mania-exchange.com/' . $dir . '/screenshot/normal/' . $this->id;
			$this->thumburl  = 'http://' . $this->prefix . '.mania-exchange.com/' . $dir . '/screenshot/small/' . $this->id;
			$this->dloadurl  = 'http://' . $this->prefix . '.mania-exchange.com/' . $dir . '/download/' . $this->id;

			if ($this->prefix == 'tm' && $this->replayid > 0) {
				$this->replayurl = 'http://' . $this->prefix . '.mania-exchange.com/replays/download/' . $this->replayid;
			} else {
				$this->replayurl = '';
			}
		}
	}  // MXInfo
}  // class MXInfo


