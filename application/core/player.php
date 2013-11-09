<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 09.11.13
 * Time: 19:32
 */

namespace ManiaControl;


class Player {
    /**
     * public properties
     */
    public $id; //Internal Id from ManiaControl
    public $pid; //Id from dedicated Server
    public $login;
    public $nickname;
    public $teamname;
    public $ip;
    public $client;
    public $ipport;
    public $zone;
    public $continent;
    public $nation;
    //public $prevstatus;
    public $isSpectator;
    public $isOfficial;
    public $language;
    public $avatar;
    public $teamid;
    public $unlocked;
    public $ladderrank;
    public $ladderscore;
    public $created;

    public $rightLevel;


    //TODO: usefull construct player without rpc info?
    //TODO: isBot properti
    //TODO: add all attributes like, allies, clublink ... just make vardump on rpc infos
    //TODO: READ ADDITIONAL INFOS FROM DATABASE
    public function __construct($rpc_infos = null){

        if ($rpc_infos) {
            $this->login = $rpc_infos['Login'];
            $this->nickname = $rpc_infos['NickName'];
            $this->pid = $rpc_infos['PlayerId'];
            $this->teamid = $rpc_infos['TeamId'];
            $this->ipport = $rpc_infos['IPAddress'];
            $this->ip = preg_replace('/:\d+/', '', $rpc_infos['IPAddress']);  // strip port
            //$this->prevstatus = false;
            $this->isSpectator = $rpc_infos['IsSpectator'];
            $this->isOfficial = $rpc_infos['IsInOfficialMode'];
            $this->teamname = $rpc_infos['LadderStats']['TeamName'];
            $this->zone = substr($rpc_infos['Path'], 6);  // strip 'World|'
            $zones = explode('|', $rpc_infos['Path']);
            if (isset($zones[1])) {
                switch ($zones[1]) {
                    case 'Europe':
                    case 'Africa':
                    case 'Asia':
                    case 'Middle East':
                    case 'North America':
                    case 'South America':
                    case 'Oceania':
                        $this->continent = $zones[1];
                        $this->nation = $zones[2];
                        break;
                    default:
                        $this->continent = '';
                        $this->nation = $zones[1];
                }
            } else {
                $this->continent = '';
                $this->nation = '';
            }
            $this->ladderrank = $rpc_infos['LadderStats']['PlayerRankings'][0]['Ranking'];
            $this->ladderscore = round($rpc_infos['LadderStats']['PlayerRankings'][0]['Score'], 2);
            $this->client = $rpc_infos['ClientVersion'];
            $this->language = $rpc_infos['Language'];
            $this->avatar = $rpc_infos['Avatar']['FileName'];
            $this->created = time();
        } else {
            // set defaults
            $this->pid = 0;
            $this->login = '';
            $this->nickname = '';
            $this->ipport = '';
            $this->ip = '';
            //$this->prevstatus = false;
            $this->isSpectator = false;
            $this->isOfficial = false;
            $this->teamname = '';
            $this->zone = '';
            $this->continent = '';
            $this->nation = '';
            $this->ladderrank = 0;
            $this->ladderscore = 0;
            $this->created = 0;
        }


        //rightlevels, 0 = user, 1 = operator, 2 = admin, 3 = superadmin, 4 = headadmin (from config)
        $this->rightLevel = 0;
    }
}

?>