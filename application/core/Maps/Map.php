<?php

namespace ManiaControl\Maps;

use ManiaControl\Formatter;
use ManiaControl\ManiaExchange\MXMapInfo;

/**
 * Map Class
 *
 * @author kremsy & steeffeen
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Map {
	/*
	 * Public Properties
	 */
	public $index = -1;
	public $name = 'undefined';
	public $rawName = '';
	public $uid = '';
	public $fileName = '';
	public $environment = '';
	public $goldTime = -1;
	public $copperPrice = -1;
	public $mapType = '';
	public $mapStyle = '';
	public $nbCheckpoints = -1;
	public $nbLaps = -1;
	/** @var MXMapInfo $mx */
	public $mx = null;
	public $authorLogin = '';
	public $authorNick = '';
	public $authorZone = '';
	public $authorEInfo = '';
	public $comment = '';
	public $titleUid = '';
	public $startTime = -1;
	public $lastUpdate = 0;

	/**
	 * Create a new Map Object from Rpc Data
	 *
	 * @param \Maniaplanet\DedicatedServer\Structures\Map $mpMap
	 * @internal param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct($mpMap = null) {
		$this->startTime = time();

		if (!$mpMap) {
			return;
		}
		$this->name          = FORMATTER::stripDirtyCodes($mpMap->name);
		$this->rawName       = $mpMap->name;
		$this->uid           = $mpMap->uId;
		$this->fileName      = $mpMap->fileName;
		$this->authorLogin   = $mpMap->author;
		$this->environment   = $mpMap->environnement;
		$this->goldTime      = $mpMap->goldTime;
		$this->copperPrice   = $mpMap->copperPrice;
		$this->mapType       = $mpMap->mapType;
		$this->mapStyle      = $mpMap->mapStyle;
		$this->nbCheckpoints = $mpMap->nbCheckpoints;
		$this->nbLaps        = $mpMap->nbLaps;

		$this->authorNick = $this->authorLogin;
	}

	/**
	 * Get's the gameType of the Current Map
	 *
	 * @return string
	 */
	public function getGame() {
		switch($this->environment) {
			case 'Storm':
				return "sm";
			case 'Canyon':
			case 'Stadium':
			case 'Valley':
				return "tm";
			default:
				return "";
		}
	}

	/**
	 * Checks if a map Update is available
	 *
	 * @return bool
	 */
	public function updateAvailable() {

		if ($this->mx && ($this->lastUpdate < strtotime($this->mx->updated) || $this->uid != $this->mx->uid)) {
			return true;
		} else {
			return false;
		}
	}
} 