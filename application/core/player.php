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
     * Private properties
     */
    private $id;
    private $pid;
    private $login;
    private $nickname;
    private $teamname;
    private $ip;
    private $client;
    private $ipport;
    private $zone;
    private $continent;
    private $nation;
    //private $prevstatus;
    private $isspectator;
    private $isofficial;
    private $language;
    private $avatar;
    private $teamid;
    private $unlocked;
    private $ladderrank;
    private $ladderscore;
    private $created;
    private $wins;
    private $newwins;
    private $timeplayed;
    private $maplist;
    private $playerlist;
    private $msgs;
    private $pmbuf;
    private $mutelist;
    private $mutebuf;
    private $style;
    private $panels;
    private $panelbg;
    private $speclogin;
    private $dedirank;
    private $disconnectionreason;

    //TODO: usefull construct player without rpc info?
    //TODO: isBot properti
    public function __construct($rpc_infos = null){
        if ($rpc_infos) {
            $this->pid = $rpc_infos['PlayerId'];
            $this->login = $rpc_infos['Login'];
            $this->nickname = $rpc_infos['NickName'];
            $this->ipport = $rpc_infos['IPAddress'];
            $this->ip = preg_replace('/:\d+/', '', $rpc_infos['IPAddress']);  // strip port
            //$this->prevstatus = false;
            $this->isspectator = $rpc_infos['IsSpectator'];
            $this->isofficial = $rpc_infos['IsInOfficialMode'];
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
            $this->teamid = $rpc_infos['TeamId'];
            $this->created = time();
        } else {
            // set defaults
            $this->pid = 0;
            $this->login = '';
            $this->nickname = '';
            $this->ipport = '';
            $this->ip = '';
            //$this->prevstatus = false;
            $this->isspectator = false;
            $this->isofficial = false;
            $this->teamname = '';
            $this->zone = '';
            $this->continent = '';
            $this->nation = '';
            $this->ladderrank = 0;
            $this->ladderscore = 0;
            $this->created = 0;
        }
    }

    /**
     * @param mixed $avatar
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }

    /**
     * @return mixed
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param string $continent
     */
    public function setContinent($continent)
    {
        $this->continent = $continent;
    }

    /**
     * @return string
     */
    public function getContinent()
    {
        return $this->continent;
    }

    /**
     * @param int $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $dedirank
     */
    public function setDedirank($dedirank)
    {
        $this->dedirank = $dedirank;
    }

    /**
     * @return mixed
     */
    public function getDedirank()
    {
        return $this->dedirank;
    }

    /**
     * @param mixed $disconnectionreason
     */
    public function setDisconnectionreason($disconnectionreason)
    {
        $this->disconnectionreason = $disconnectionreason;
    }

    /**
     * @return mixed
     */
    public function getDisconnectionreason()
    {
        return $this->disconnectionreason;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ipport
     */
    public function setIpport($ipport)
    {
        $this->ipport = $ipport;
    }

    /**
     * @return string
     */
    public function getIpport()
    {
        return $this->ipport;
    }

    /**
     * @param boolean $isofficial
     */
    public function setIsofficial($isofficial)
    {
        $this->isofficial = $isofficial;
    }

    /**
     * @return boolean
     */
    public function getIsofficial()
    {
        return $this->isofficial;
    }

    /**
     * @param boolean $isspectator
     */
    public function setIsspectator($isspectator)
    {
        $this->isspectator = $isspectator;
    }

    /**
     * @return boolean
     */
    public function getIsspectator()
    {
        return $this->isspectator;
    }

    /**
     * @param int $ladderrank
     */
    public function setLadderrank($ladderrank)
    {
        $this->ladderrank = $ladderrank;
    }

    /**
     * @return int
     */
    public function getLadderrank()
    {
        return $this->ladderrank;
    }

    /**
     * @param int $ladderscore
     */
    public function setLadderscore($ladderscore)
    {
        $this->ladderscore = $ladderscore;
    }

    /**
     * @return int
     */
    public function getLadderscore()
    {
        return $this->ladderscore;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param mixed $maplist
     */
    public function setMaplist($maplist)
    {
        $this->maplist = $maplist;
    }

    /**
     * @return mixed
     */
    public function getMaplist()
    {
        return $this->maplist;
    }

    /**
     * @param mixed $msgs
     */
    public function setMsgs($msgs)
    {
        $this->msgs = $msgs;
    }

    /**
     * @return mixed
     */
    public function getMsgs()
    {
        return $this->msgs;
    }

    /**
     * @param mixed $mutebuf
     */
    public function setMutebuf($mutebuf)
    {
        $this->mutebuf = $mutebuf;
    }

    /**
     * @return mixed
     */
    public function getMutebuf()
    {
        return $this->mutebuf;
    }

    /**
     * @param mixed $mutelist
     */
    public function setMutelist($mutelist)
    {
        $this->mutelist = $mutelist;
    }

    /**
     * @return mixed
     */
    public function getMutelist()
    {
        return $this->mutelist;
    }

    /**
     * @param string $nation
     */
    public function setNation($nation)
    {
        $this->nation = $nation;
    }

    /**
     * @return string
     */
    public function getNation()
    {
        return $this->nation;
    }

    /**
     * @param mixed $newwins
     */
    public function setNewwins($newwins)
    {
        $this->newwins = $newwins;
    }

    /**
     * @return mixed
     */
    public function getNewwins()
    {
        return $this->newwins;
    }

    /**
     * @param string $nickname
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;
    }

    /**
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * @param mixed $panelbg
     */
    public function setPanelbg($panelbg)
    {
        $this->panelbg = $panelbg;
    }

    /**
     * @return mixed
     */
    public function getPanelbg()
    {
        return $this->panelbg;
    }

    /**
     * @param mixed $panels
     */
    public function setPanels($panels)
    {
        $this->panels = $panels;
    }

    /**
     * @return mixed
     */
    public function getPanels()
    {
        return $this->panels;
    }

    /**
     * @param int $pid
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param mixed $playerlist
     */
    public function setPlayerlist($playerlist)
    {
        $this->playerlist = $playerlist;
    }

    /**
     * @return mixed
     */
    public function getPlayerlist()
    {
        return $this->playerlist;
    }

    /**
     * @param mixed $pmbuf
     */
    public function setPmbuf($pmbuf)
    {
        $this->pmbuf = $pmbuf;
    }

    /**
     * @return mixed
     */
    public function getPmbuf()
    {
        return $this->pmbuf;
    }

    /**
     * @param mixed $speclogin
     */
    public function setSpeclogin($speclogin)
    {
        $this->speclogin = $speclogin;
    }

    /**
     * @return mixed
     */
    public function getSpeclogin()
    {
        return $this->speclogin;
    }

    /**
     * @param mixed $style
     */
    public function setStyle($style)
    {
        $this->style = $style;
    }

    /**
     * @return mixed
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param mixed $teamid
     */
    public function setTeamid($teamid)
    {
        $this->teamid = $teamid;
    }

    /**
     * @return mixed
     */
    public function getTeamid()
    {
        return $this->teamid;
    }

    /**
     * @param string $teamname
     */
    public function setTeamname($teamname)
    {
        $this->teamname = $teamname;
    }

    /**
     * @return string
     */
    public function getTeamname()
    {
        return $this->teamname;
    }

    /**
     * @param mixed $timeplayed
     */
    public function setTimeplayed($timeplayed)
    {
        $this->timeplayed = $timeplayed;
    }

    /**
     * @return mixed
     */
    public function getTimeplayed()
    {
        return $this->timeplayed;
    }

    /**
     * @param mixed $unlocked
     */
    public function setUnlocked($unlocked)
    {
        $this->unlocked = $unlocked;
    }

    /**
     * @return mixed
     */
    public function getUnlocked()
    {
        return $this->unlocked;
    }

    /**
     * @param mixed $wins
     */
    public function setWins($wins)
    {
        $this->wins = $wins;
    }

    /**
     * @return mixed
     */
    public function getWins()
    {
        return $this->wins;
    }

    /**
     * @param string $zone
     */
    public function setZone($zone)
    {
        $this->zone = $zone;
    }

    /**
     * @return string
     */
    public function getZone()
    {
        return $this->zone;
    }
}

?>