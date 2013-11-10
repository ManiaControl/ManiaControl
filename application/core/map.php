<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 10.11.13
 * Time: 16:46
 */

namespace ManiaControl;


class map {
    public $id = 0;
    public $name = '';
    public $uid = 0;
    public $filename = '';
    public $author = '';
    public $environment = '';
    public $mood = '';
    public $bronzetime; //format?
    public $silvertime; //format?
    public $goldtime;   //format?
    public $authortime; //format?
    public $copperprice = 0;
    public $laprace = 0;
    public $forcedlaps = 0;
    public $nblaps = 0;
    public $nbchecks = 0;
    public $score = 0;
    public $starttime = 0;
    public $maptype; //format?
    public $mapstyle; //format?
    public $titleuid; //format?
    public $gbx; //format?
    public $mx; //format?
    public $authorNick; //format?
    public $authorZone; //format?
    public $authorEInfo; //format?

    //Todo: check RPC infos
    // instantiates the map with an RPC response
    public function __construct($rpc_infos = null) {
        $this->id = 0;
        if ($rpc_infos) {
            $this->name = stripNewlines($rpc_infos['Name']);
            $this->uid = $rpc_infos['UId'];
            $this->filename = $rpc_infos['FileName'];
            $this->author = $rpc_infos['Author'];
            $this->environment = $rpc_infos['Environnement'];
            $this->mood = $rpc_infos['Mood'];
            $this->bronzetime = $rpc_infos['BronzeTime'];
            $this->silvertime = $rpc_infos['SilverTime'];
            $this->goldtime = $rpc_infos['GoldTime'];
            $this->authortime = $rpc_infos['AuthorTime'];
            $this->copperprice = $rpc_infos['CopperPrice'];
            $this->laprace = $rpc_infos['LapRace'];
            $this->forcedlaps = 0;
            $this->nblaps = $rpc_infos['NbLaps'];
            $this->nbchecks = $rpc_infos['NbCheckpoints'];
            $this->maptype = $rpc_infos['MapType'];
            $this->mapstyle = $rpc_infos['MapStyle'];

            $this->starttime = time();
        } else {
            $this->name = 'undefined';
        }


    }
} 