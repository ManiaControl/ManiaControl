<?php

namespace ManiaControl\Maps;

use ManiaControl\ManiaControl;

/**
 * Map class
 *
 * @author kremsy & steeffeen
 */
class Map {
	
	/**
	 * Public properties
	 */
	public $index = -1;
	public $name = 'undefined';
	public $uid = '';
	public $fileName = '';
	public $environment = '';
	public $goldTime; // TODO: format?
	public $copperPrice = 0;
	public $mapType = '';
	public $mapStyle = '';
	public $mx = null;
	public $authorLogin = '';
	public $authorNick = '';
	public $authorZone = '';
	public $authorEInfo = '';
	public $comment = '';
	public $titleUid = '';
	public $startTime = 0;
	public $mapFetcher = null;
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new map object from rpc data
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 * @param array $rpc_infos        	
	 */
	public function __construct(ManiaControl $maniaControl, $rpc_infos = null) {
		$this->maniaControl = $maniaControl;
		$this->startTime = time();
		
		if (!$rpc_infos) {
			return;
		}
		$this->name = $rpc_infos['Name']; // in aseco stripped new lines on name
		$this->uid = $rpc_infos['UId'];
		$this->fileName = $rpc_infos['FileName'];
		$this->authorLogin = $rpc_infos['Author'];
		$this->environment = $rpc_infos['Environnement'];
		$this->goldTime = $rpc_infos['GoldTime'];
		$this->copperPrice = $rpc_infos['CopperPrice'];
		$this->mapType = $rpc_infos['MapType'];
		$this->mapStyle = $rpc_infos['MapStyle'];
		
		$mapsDirectory = $this->maniaControl->server->getMapsDirectory();
		if ($this->maniaControl->server->checkAccess($mapsDirectory)) {
			$this->mapFetcher = new \GBXChallMapFetcher(true);
			try {
				$this->mapFetcher->processFile($mapsDirectory . $this->fileName);
			}
			catch (Exception $e) {
				trigger_error($e->getMessage(), E_USER_WARNING);
			}
			$this->authorNick = $this->mapFetcher->authorNick;
			$this->authorEInfo = $this->mapFetcher->authorEInfo;
			$this->authorZone = $this->mapFetcher->authorZone;
			$this->comment = $this->mapFetcher->comment;
		}
		
		// TODO: define timeout if mx is down
		$serverInfo = $this->maniaControl->server->getSystemInfo();
		$title = strtoupper(substr($serverInfo['TitleId'], 0, 2));
		$this->mx = new \MXInfoFetcher($title, $this->uid, false);
	}
} 