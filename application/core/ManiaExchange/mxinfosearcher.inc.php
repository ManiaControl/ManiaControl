<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * MXInfoSearcher - Search info for TM2/SM/QM maps from ManiaExchange
 * Created by Xymph <tm@gamers.org> based on:
 * http://api.mania-exchange.com/
 * http://tm.mania-exchange.com/api
 * http://sm.mania-exchange.com/api
 * http://tm.mania-exchange.com/threads/view/218
 * Derived from TMXInfoSearcher
 *
 * v1.6: Added Countable interface to searcher class
 * v1.5: Added MXInfo $titlepack (TM2/SM); add support for environment matching
 * v1.4: Fixed an error checking bug
 * v1.3: Added MXInfo $maptype (TM2/SM)
 * v1.2: Updated to use MX API v2.0 and add/fix support for SM; added MXInfo
 *       $trkvalue (TM2, equals deprecated $lbrating), $unlimiter (TM2/SM),
 *       $rating/$ratingex/$ratingcnt (SM)
 * v1.1: Added URLs to downloadable replays
 * v1.0: Initial release
 */
class MXInfoSearcher implements Iterator,Countable {

	public $error;
	protected $maps = array();
	private $section;
	private $prefix;

	/**
	 * Searches MX for maps matching name, author and/or environment;
	 * or search MX for the 10 most recent maps
	 *
	 * @param String $game
	 *        MX section for 'TM2', 'SM', 'QM'
	 * @param String $name
	 *        The map name to search for (partial, case-insensitive match)
	 * @param String $author
	 *        The map author to search for (partial, case-insensitive match)
	 * @param String $env
	 *        The environment to search for (exact case-sensitive match from:
	 *        TMCanyon, TMStadium, TMValley, SMStorm, ...)
	 * @param Boolean $recent
	 *        If true, ignore search parameters and just return 10 newest maps
	 *        (max. one per author)
	 * @return MXInfoSearcher
	 *        If ->valid() is false, no matching map was found;
	 *        otherwise, an iterator of MXInfo objects for a 'foreach' loop.
	 *        Returns at most 100 maps ($maxpage * 20).
	 */
	public function __construct($game, $name, $author, $env, $recent) {

		$this->section = $game;
		switch ($game) {
		case 'TM2':
			$this->prefix = 'tm';
			break;
		case 'SM':
			$this->prefix = 'sm';
			break;
		case 'QM':
			$this->prefix = 'qm';
			break;
		default:
			$this->prefix = '';
			return;
		}

		$this->error = '';
		if ($recent) {
			$this->maps = $this->getRecent();
		} else {
			$this->maps = $this->getList($name, $author, $env);
		}
	}  // __construct

	// define standard Iterator functions
	public function rewind() {
		reset($this->maps);
	}
	public function current() {
		return new MXInfo($this->section, $this->prefix, current($this->maps));
	}
	public function next() {
		return new MXInfo($this->section, $this->prefix, next($this->maps));
	}
	public function key() {
		return key($this->maps);
	}
	public function valid() {
		return (current($this->maps) !== false);
	}
	// define standard Countable function
	public function count() {
		return count($this->maps);
	}

	private function getRecent() {

		// get 10 most recent maps
		if ($this->prefix == 'tm')
			$dir = 'tracks';
		else // 'sm' || 'qm'
			$dir = 'maps';
		$url = 'http://api.mania-exchange.com/' . $this->prefix . '/' . $dir . '/list/latest';
		$file = $this->get_file($url);
		if ($file === false) {
			$this->error = 'Connection or response error on ' . $url;
			return array();
		} elseif ($file === -1) {
			$this->error = 'Timed out while reading data from ' . $url;
			return array();
		} elseif ($file == '') {
			$this->error = 'No data returned from ' . $url;
			return array();
		}

		$mx = json_decode($file);
		if ($mx === null) {
			$this->error = 'Cannot decode recent JSON data from ' . $url;
			return array();
		}

		// return list of maps as array of MX objects
		return $mx;
	}  // getRecent

	private function getList($name, $author, $env) {

		$maxpage = 5;  // max. 100 maps

		// compile search URL
		$url = 'http://' . $this->prefix . '.mania-exchange.com/tracksearch?api=on';
		if ($name != '')
			$url .= '&trackname=' . $name;
		if ($author != '')
			$url .= '&author=' . $author;
		switch ($env) {
			case 'TMCanyon':
			case 'SMStorm':
				$url .= '&environments=1';
				break;
			case 'TMStadium':
				$url .= '&environments=2';
				break;
			case 'TMValley':
				$url .= '&environments=3';
				break;
		}
		$url .= '&page=';

		$maps = array();
		$page = 1;
		$done = false;

		// get results 20 maps at a time
		while ($page <= $maxpage && !$done) {
			$file = $this->get_file($url . $page);
			if ($file === false) {
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
			}

			$mx = json_decode($file);
			if ($mx === null) {
				$this->error = 'Cannot decode searched JSON data from ' . $url;
				return array();
			}

			// check for results
			if (!empty($mx)) {
				$maps = array_merge($maps, $mx);
				$page++;
			} else {
				$done = true;
			}
		}

		// return list of maps as array of MX objects
		return $maps;
	}  // getList

	// Simple HTTP Get function with timeout
	// ok: return string || error: return false || timeout: return -1
	private function get_file($url) {

		$url = parse_url($url);
		$port = isset($url['port']) ? $url['port'] : 80;
		$query = isset($url['query']) ? "?" . $url['query'] : "";

		$fp = @fsockopen($url['host'], $port, $errno, $errstr, 4);
		if (!$fp)
			return false;

		fwrite($fp, 'GET ' . $url['path'] . $query . " HTTP/1.0\r\n" .
		            'Host: ' . $url['host'] . "\r\n" .
		            'Content-Type: application/json' . "\r\n" .
		            'User-Agent: MXInfoSearcher (' . PHP_OS . ")\r\n\r\n");
		stream_set_timeout($fp, 2);
		$res = '';
		$info['timed_out'] = false;
		while (!feof($fp) && !$info['timed_out']) {
			$res .= fread($fp, 512);
			$info = stream_get_meta_data($fp);
		}
		fclose($fp);

		if ($info['timed_out']) {
			return -1;
		} else {
			if (substr($res, 9, 3) != '200')
				return false;
			$page = explode("\r\n\r\n", $res, 2);
			return trim($page[1]);
		}
	}  // get_file
}  // class MXInfoSearcher


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
	 * @param String $section
	 *        MX section
	 * @param String $prefix
	 *        MX URL prefix
	 * @param Object $map
	 *        The MX map data from MXInfoSearcher
	 * @return MXInfo
	 */
	public function MXInfo($section, $prefix, $mx) {

		$this->section  = $section;
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
?>
