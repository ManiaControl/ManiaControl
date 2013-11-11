<?php

namespace ManiaControl;

/**
 * Map Object
 *
 * @author kremsy & steeffeen
 */

class Map {
	/**
	 * Private properties
	 */
	private $maniaControl = 0;

	/**
	 * Public properties
	 */
	public $mapFetcher = null;

    public $id = 0;
    public $name = '';
    public $uid = 0;
    public $fileName = '';
    public $environment = '';
    public $goldTime;   //format?
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

	public $starttime = 0;

    // instantiates the map with an RPC response
    public function __construct(ManiaControl $maniaControl, $rpc_infos = null) {
        $this->maniaControl = $maniaControl;

        if ($rpc_infos) {
            $this->name = $rpc_infos['Name']; //in aseco stripped new lines on name
            $this->uid = $rpc_infos['UId'];
            $this->fileName = $rpc_infos['FileName'];
            $this->authorLogin = $rpc_infos['Author'];
            $this->environment = $rpc_infos['Environnement'];
            $this->goldTime = $rpc_infos['GoldTime'];
            $this->copperPrice = $rpc_infos['CopperPrice'];
            $this->mapType = $rpc_infos['MapType'];
            $this->mapStyle = $rpc_infos['MapStyle'];

			$this->mapFetcher = new \GBXChallMapFetcher(true);

			try{
				$this->mapFetcher->processFile($this->maniaControl->server->getMapsDirectory() . $this->fileName);
			}    catch (Exception $e){
				trigger_error($e->getMessage(), E_USER_WARNING);
			}
			$this->authorNick = $this->mapFetcher->authorNick;
			$this->authorEInfo = $this->mapFetcher->authorEInfo;
			$this->authorZone = $this->mapFetcher->authorZone;
			$this->comment = $this->mapFetcher->comment;
			//additional properties anyway in the mapfetcher object

			//TODO: change to SM to gameerkennung
			//TODO: define timeout if mx is down
			$this->mx = new \MXInfoFetcher('SM', $this->uid, false); //SM -> change to gameerkennung
        } else {
            $this->name = 'undefined';
        }

		$this->starttime = time();
    }
} 