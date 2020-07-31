<?php

namespace ManiaControl\Maps;

use ManiaControl\General\Dumpable;
use ManiaControl\General\DumpTrait;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaExchange\MXMapInfo;
use ManiaControl\Utils\Formatter;

/**
 * ManiaControl Map Model Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Map implements Dumpable, UsageInformationAble {
	use DumpTrait, UsageInformationTrait;

	//Minimum Lightmap Version for the Update Check
	const MIN_LIGHTMAP_VERSION = 7;

	/*
	 * Public properties
	 */
	public $index         = -1;
	public $name          = 'undefined';
	public $rawName       = null;
	public $uid           = null;
	public $fileName      = null;
	public $environment   = null;
	public $authorTime    = -1;
	public $goldTime      = -1;
	public $silverTime    = -1;
	public $bronzeTime    = -1;
	public $copperPrice   = -1;
	public $mapType       = null;
	public $mapStyle      = null;
	public $nbCheckpoints = -1;
	public $nbLaps        = -1;
	/** @var MXMapInfo $mx */
	public $mx          = null;
	public $authorLogin = null;
	public $authorNick  = null;
	public $authorZone  = null;
	public $authorEInfo = null;
	public $comment     = null;
	public $titleUid    = null;
	public $startTime   = -1;
	public $lastUpdate  = 0;
	public $karma       = null;

	/**
	 * Construct a new map instance from xmlrpc data
	 *
	 * @param \Maniaplanet\DedicatedServer\Structures\Map $mpMap
	 */
	public function __construct($mpMap = null) {
		$this->startTime = time();

		if (!$mpMap) {
			return;
		}
		$this->name          = Formatter::stripDirtyCodes($mpMap->name);
		$this->rawName       = $mpMap->name;
		$this->uid           = $mpMap->uId;
		$this->fileName      = $mpMap->fileName;
		$this->authorLogin   = $mpMap->author;
		$this->environment   = $mpMap->environnement;
		$this->authorTime    = $mpMap->authorTime;
		$this->goldTime      = $mpMap->goldTime;
		$this->silverTime    = $mpMap->silverTime;
		$this->bronzeTime    = $mpMap->bronzeTime;
		$this->copperPrice   = $mpMap->copperPrice;
		$this->mapType       = $mpMap->mapType;
		$this->mapStyle      = $mpMap->mapStyle;
		$this->nbCheckpoints = $mpMap->nbCheckpoints;
		$this->nbLaps        = $mpMap->nbLaps;

		$this->authorNick = $this->authorLogin;
	}

	/**
	 * Get the escaped map name
	 *
	 * @return string
	 */
	public function getEscapedName() {
		return Formatter::escapeText($this->name);
	}

	/**
	 * Get the game type of the map
	 *
	 * @return string
	 */
	public function getGame() {
		switch ($this->environment) {
			case 'Storm':
				return 'sm';
			case 'Canyon':
			case 'Stadium':
			case 'Valley':
			case 'Lagoon':
				return 'tm';
		}
		return null;
	}

	/**
	 * Check whether a map update is available
	 *
	 * @return bool
	 */
	public function updateAvailable() {
		//Check if MX Object is existing
		if (!$this->mx) {
			return false;
		}

		//Check if the Lightmap verison on MX surpasses the min Lightmap version
		if ($this->mx->lightmap < self::MIN_LIGHTMAP_VERSION) {
			return false;
		}

		//Check if last Map update is older than the MX Maptime
		if ($this->lastUpdate < strtotime($this->mx->updated)) {
			return true;
		}

		//Check if UIDs are different
		if ($this->uid !== $this->mx->uid) {
			return true;
		}

		return false;
	}

}
