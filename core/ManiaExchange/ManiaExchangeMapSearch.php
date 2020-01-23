<?php

namespace ManiaControl\ManiaExchange;

use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Mania Exchange Map Searching Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaExchangeMapSearch implements UsageInformationAble {
	use UsageInformationTrait;

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
	const SEARCH_ORDER_TRACK_VALUE_LTH    = 24;
	const SEARCH_ORDER_TRACK_VALUE_HTL    = 25;
	const SEARCH_ORDER_ONLINE_RATING_LTH  = 26;
	const SEARCH_ORDER_ONLINE_RATING_HTL  = 27;

	//Special Search Orders (mode parameter): https://api.mania-exchange.com/documents/enums#modes
	const SEARCH_ORDER_SPECIAL_DEFAULT                  = 0;
	const SEARCH_ORDER_SPECIAL_USER_TRACKS              = 1;
	const SEARCH_ORDER_SPECIAL_LATEST_TRACKS            = 2;
	const SEARCH_ORDER_SPECIAL_RECENTLY_AWARDED         = 3;
	const SEARCH_ORDER_SPECIAL_BEST_OF_WEEK_AWARDS      = 4;
	const SEARCH_ORDER_SPECIAL_BEST_OF_MONTH_AWARDS     = 5;
	const SEARCH_ORDER_SPECIAL_MX_SUPPORTER_TRACKS      = 10;
	const SEARCH_ORDER_SPECIAL_DUO_ACCOUNT_TRACKS       = 11;
	const SEARCH_ORDER_SPECIAL_MOST_COMPETITIVE_WEEK    = 19;
	const SEARCH_ORDER_SPECIAL_MOST_COMPETITIVE_MONTH   = 20;
	const SEARCH_ORDER_SPECIAL_BEST_ONLINE_RATING_WEEK  = 21;
	const SEARCH_ORDER_SPECIAL_BEST_ONLINE_RATING_MONTH = 22;

	//Private Properties
	private $url         = "";
	private $titlePrefix = "";

	private $mode              = null;
	private $mapName           = null;
	private $authorName        = null;
	private $mod               = null;
	private $authorId          = null;
	private $maniaScriptType   = null;
	private $titlePack         = null;
	private $replayType        = null;
	private $style             = null;
	private $length            = null;
	private $lengthOperator    = null;
	private $priorityOrder     = null;
	private $secondaryOrder    = null;
	private $environments      = null;
	private $vehicles          = null;
	private $page              = null;
	private $mapLimit          = null;
	private $unreleased        = null;
	private $mapGroup          = null;
	private $commentsMinLength = null;
	private $customScreenshot  = null;
	private $minExeBuild       = null;
	private $envMix            = null;
	private $ghostBlocks       = null;
	private $embeddedObjects   = null;
	private $key               = null;
	private $mp4               = null;

	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	//TODO use class by mxlist

	/**
	 * Construct map manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->titlePrefix = $this->maniaControl->getMapManager()->getCurrentMap()->getGame();

		$this->url = 'https://' . $this->titlePrefix . '.mania-exchange.com/tracksearch2/search?api=on';

		/*if ($key = $this->maniaControl->getSettingManager()->getSettingValue($this->maniaControl->getMapManager()->getMXManager(), ManiaExchangeManager::SETTING_MX_KEY)) {
			$this->url .= "&key=" . $key;
		}*/


		//Set some defaults:
		$this->mapLimit      = 100;
		$this->priorityOrder = self::SEARCH_ORDER_UPDATED_NEWEST;
		$this->mp4           = true;

		//Set Min Exe Build Default for games which are not Trackmania
		/*if ($this->titlePrefix !== "tm") {
			$this->minExeBuild = ManiaExchangeManager::MIN_EXE_BUILD;
		}*/

		//Set MapTypes
		try {
			$scriptInfos           = $this->maniaControl->getClient()->getModeScriptInfo();
			$mapTypes              = $scriptInfos->compatibleMapTypes;
			$this->maniaScriptType = $mapTypes;
		} catch (GameModeException $e) {
		}

	}

	/**
	 * Fetch a MapList Asynchronously
	 *
	 * @param callable $function
	 */
	public function fetchMapsAsync(callable $function) {
		// compile search URL
		$parameters = "";

		if ($this->mode) {
			$parameters .= "&mode=" . $this->mode;
		}
		if ($this->mapName) {
			$parameters .= "&trackname=" . urlencode($this->mapName);
		}
		if ($this->authorName) {
			$parameters .= "&author=" . urlencode($this->authorName);
		}
		if ($this->mod) {
			$parameters .= "&mod=" . urlencode($this->mod);
		}
		if ($this->authorId) {
			$parameters .= "&authorid= " . $this->authorId;
		}
		if ($this->maniaScriptType) {
			$parameters .= "&mtype=" . urlencode($this->maniaScriptType);
		}
		if ($this->titlePack) {
			$parameters .= "&tpack=" . urlencode($this->titlePack);
		}
		if ($this->replayType) {
			$parameters .= "&rytpe=" . $this->replayType;
		}
		if ($this->style) {
			$parameters .= "&style=" . $this->style;
		}
		if ($this->length) {
			$parameters .= "&length=" . $this->length;
		}
		if ($this->lengthOperator) {
			$parameters .= "&lengthop=" . $this->lengthOperator;
		}
		if ($this->priorityOrder) {
			$parameters .= "&priord=" . $this->priorityOrder;
		}
		if ($this->secondaryOrder) {
			$parameters .= "&secord=" . $this->secondaryOrder;
		}
		if ($this->environments) {
			$parameters .= "&environments=" . $this->environments;
		}
		if ($this->vehicles) {
			$parameters .= "&vehicles=" . $this->vehicles;
		}
		if ($this->page) {
			$parameters .= "&page=" . $this->page;
		}
		if ($this->mapLimit) {
			$parameters .= "&limit=" . $this->mapLimit;
		}
		if (isset($this->unreleased)) {
			$parameters .= "&unreleased=" . (int) $this->unreleased;
		}
		if ($this->mapGroup) {
			$parameters .= "&mapgroup=" . $this->mapGroup;
		}
		if ($this->commentsMinLength) {
			$parameters .= "&commentsminlength=" . $this->commentsMinLength;
		}
		if (isset($this->customScreenshot)) {
			$parameters .= "&customscreenshot=" . $this->customScreenshot;
		}
		if ($this->minExeBuild) {
			$parameters .= "&minexebuild=" . urlencode($this->minExeBuild);
		}
		if (isset($this->envMix)) {
			$parameters .= "&envmix=" . (int) $this->envMix;
		}
		if (isset($this->ghostBlocks)) {
			$parameters .= "&ghostblocks=" . (int) $this->ghostBlocks;
		}
		if (isset($this->embeddedObjects)) {
			$parameters .= "&embeddedobjects=" . (int) $this->embeddedObjects;
		}
		if (isset($this->key)) {
			$parameters .= "&key=" . $this->key;
		}
		if (isset($this->mp4)) {
			$parameters .= "&mp4=" . $this->mp4;
		}

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $this->url . $parameters);
		$asyncHttpRequest->setContentType(AsyncHttpRequest::CONTENT_TYPE_JSON);
		$asyncHttpRequest->setCallable(function ($mapInfo, $error) use (&$function) {
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
					array_push($maps, new MXMapInfo($this->titlePrefix, $map));
				}
			}

			call_user_func($function, $maps);
		});

		$asyncHttpRequest->getData();
	}


	/**
	 * Get the Current Environment by String
	 *
	 * @param string $env
	 * @return int
	 */
	public static function getEnvironment($env) {
		switch ($env) {
			case 'TMCanyon':
				return 1;
			case 'TMStadium':
				return 2;
			case 'TMValley':
				return 3;
			case 'TMLagoon':
				return 4;
			default:
				return -1;
		}
	}

	/**
	 * @param int $mode
	 */
	public function setMode($mode) {
		$this->mode = $mode;
	}

	/**
	 * @param string $mapName
	 */
	public function setMapName($mapName) {
		$this->mapName = $mapName;
	}

	/**
	 * @param string $authorName
	 */
	public function setAuthorName($authorName) {
		$this->authorName = $authorName;
	}

	/**
	 * @param int $authorId
	 */
	public function setAuthorId($authorId) {
		$this->authorId = $authorId;
	}

	/**
	 * @param string $maniaScriptType
	 */
	public function setManiaScriptType($maniaScriptType) {
		$this->maniaScriptType = $maniaScriptType;
	}

	/**
	 * @param string $mod
	 */
	public function setMod($mod) {
		$this->mod = $mod;
	}

	/**
	 * @param string $titlePack
	 */
	public function setTitlePack($titlePack) {
		$this->titlePack = $titlePack;
	}

	/**
	 * @param int $replayType
	 */
	public function setReplayType($replayType) {
		$this->replayType = $replayType;
	}

	/**
	 * @param int $length
	 */
	public function setLength($length) {
		$this->length = $length;
	}

	/**
	 * @param int $style
	 */
	public function setStyle($style) {
		$this->style = $style;
	}

	/**
	 * @param int $lengthOperator
	 */
	public function setLengthOperator($lengthOperator) {
		$this->lengthOperator = $lengthOperator;
	}

	/**
	 * @param int $secondaryOrder
	 */
	public function setSecondarySortOrder($secondaryOrder) {
		$this->secondaryOrder = $secondaryOrder;
	}

	/**
	 * @param int $priorityOrder
	 */
	public function setPrioritySortOrder($priorityOrder) {
		$this->priorityOrder = $priorityOrder;
	}

	/**
	 * @param string $environments
	 */
	public function setEnvironments($environments) {
		$this->environments = $environments;
	}

	/**
	 * @param int $page
	 */
	public function setPage($page) {
		$this->page = $page;
	}

	/**
	 * @param string $vehicles
	 */
	public function setVehicles($vehicles) {
		$this->vehicles = $vehicles;
	}

	/**
	 * @param bool $unreleased
	 */
	public function setUnreleased($unreleased) {
		$this->unreleased = $unreleased;
	}

	/**
	 * @param int $mapGroup
	 */
	public function setMapGroup($mapGroup) {
		$this->mapGroup = $mapGroup;
	}

	/**
	 * @param int $commentsMinLength
	 */
	public function setCommentsMinLength($commentsMinLength) {
		$this->commentsMinLength = $commentsMinLength;
	}

	/**
	 * @param bool $customScreenshot
	 */
	public function setCustomScreenshot($customScreenshot) {
		$this->customScreenshot = $customScreenshot;
	}

	/**
	 * @param bool $envMix
	 */
	public function setEnvMix($envMix) {
		$this->envMix = $envMix;
	}

	/**
	 * @param string $minExeBuild
	 */
	public function setMinExeBuild($minExeBuild) {
		$this->minExeBuild = $minExeBuild;
	}

	/**
	 * @param bool $ghostBlocks
	 */
	public function setGhostBlocks($ghostBlocks) {
		$this->ghostBlocks = $ghostBlocks;
	}

	/**
	 * @param bool $embeddedObjects
	 */
	public function setEmbeddedObjects($embeddedObjects) {
		$this->embeddedObjects = $embeddedObjects;
	}

	/**
	 * @param null $mapLimit
	 */
	public function setMapLimit($mapLimit) {
		$this->mapLimit = $mapLimit;
	}

	/**
	 * @param null $key
	 */
	public function setKey($key) {
		$this->key = $key;
	}
}