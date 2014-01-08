<?php
namespace ManiaControl\Maps;

use ManiaControl\FileUtil;
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

	public function getList($maxMapsReturned = 100, $searchOrder = self::SEARCH_ORDER_UPLOADED_NEWEST, $env = '') {
		//Get Title Id
		$titleId = $this->maniaControl->server->titleId;
		$title   = strtoupper(substr($titleId, 0, 2));

		//Get MapTypes
		$this->maniaControl->client->query('GetModeScriptInfo');
		$scriptInfos = $this->maniaControl->client->getResponse();

		$mapTypes = $scriptInfos["CompatibleMapTypes"];
		$mapTypeArray = explode(",", $mapTypes);

		var_dump($mapTypes);
		var_dump($mapTypeArray);

		// compile search URL
		$url = 'http://' . $title . '.mania-exchange.com/tracksearch?api=on';
		/*	if ($name != '')
				$url .= '&trackname=' . $name;
			if ($author != '')
				$url .= '&author=' . $author;*/

		if($env != '') {
			$url .= '&environments=' . $this->getEnvironment($env);
		}

		$url .= '&priord=' . $searchOrder;
		$url .= '&limit=' . $maxMapsReturned;
		$url .= '&mtype=' . $mapTypeArray[0];

		var_dump($url);
		$mapInfo = FileUtil::loadFile($url, "application/json");

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

		$mx = json_decode($mapInfo);
		if($mx === null) {
			$this->error = 'Cannot decode searched JSON data from ' . $url;
			return array();
		}

		var_dump($mx);

		// return list of maps as array of MX objects
		//return $maps;

	}

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