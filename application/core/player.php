<?php

namespace ManiaControl;

/**
 * Class representing players
 *
 * @author Kremsy & Steff
 */
class Player {
	/**
	 * Public properties
	 */
	public $index = -1;
	public $pid = -1;
	public $login = '';
	public $nickname = '';
	public $isFakePlayer = false;
	public $teamName = '';
	public $ip = '';
	public $ipFull = '';
	public $clientVersion = '';
	public $zone = '';
	public $continent = '';
	public $nation = '';
	public $isSpectator = false;
	public $isOfficial = false;
	public $language = '';
	public $avatar = '';
	public $teamId; // TODO: default value
	public $unlocked; // TODO: default value
	public $ladderRank = -1;
	public $ladderScore = -1;
	public $created = -1;
	public $rightLevel = 0;
	
	// TODO: usefull construct player without rpc info?
	// TODO: add all attributes like, allies, clublink ... just make vardump on rpc infos
	// TODO: READ ADDITIONAL INFOS FROM DATABASE
	/**
	 * Construct a player
	 *
	 * @param array $rpcInfos        	
	 */
	public function __construct($rpcInfos) {
		$this->created = time();
		if (!$rpcInfos) {
			return;
		}
		
		$this->login = $rpcInfos['Login'];
		$this->isFakePlayer = (stripos($this->login, '*') !== false);
		$this->nickname = $rpcInfos['NickName'];
		$this->pid = $rpcInfos['PlayerId'];
		$this->teamId = $rpcInfos['TeamId'];
		$this->ipFull = $rpcInfos['IPAddress'];
		$this->ip = preg_replace('/:\d+/', '', $this->ipFull);
		$this->isSpectator = $rpcInfos['IsSpectator'];
		$this->isOfficial = $rpcInfos['IsInOfficialMode'];
		$this->teamName = $rpcInfos['LadderStats']['TeamName'];
		$this->zone = substr($rpcInfos['Path'], 6);
		$zones = explode('|', $rpcInfos['Path']);
		if (isset($zones[1])) {
			if (isset($zones[2])) {
				$this->continent = $zones[1];
				$this->nation = $zones[2];
			}
			else {
				$this->nation = $zones[1];
			}
		}
		$this->ladderRank = $rpcInfos['LadderStats']['PlayerRankings'][0]['Ranking'];
		$this->ladderScore = round($rpcInfos['LadderStats']['PlayerRankings'][0]['Score'], 2);
		$this->clientVersion = $rpcInfos['ClientVersion'];
		$this->language = $rpcInfos['Language'];
		$this->avatar = $rpcInfos['Avatar']['FileName'];
	}
}

?>