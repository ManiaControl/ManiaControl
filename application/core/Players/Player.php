<?php

namespace ManiaControl\Players;
use ManiaControl\Formatter;

/**
 * Class representing players
 *
 * @author kremsy & steeffeen
 */
class Player {
	/**
	 * Public properties
	 */
	public $index = -1;
	public $pid = -1;
	public $login = '';
	public $nickname = '';
	public $path = '';
	public $authLevel = 0;
	public $joinCount = 0;
	public $totalPlayed = 0;
	public $language = '';
	public $avatar = '';
	public $allies = array();
	public $clubLink = '';
	public $teamId = -1;
	public $isSpectator = false;
	public $isOfficial = false;
	public $isReferee = false;
	public $ladderScore = -1.;
	public $ladderRank = -1;
	public $joinTime = -1;
	public $ipAddress = '';
	/**
	 * Construct a player from XmlRpc data
	 *
	 * @param array $rpcInfos        	
	 */
	public function __construct(array $rpcInfos) {
		if (!$rpcInfos) {
			return;
		}
		$this->pid = $rpcInfos['PlayerId'];
		$this->login = $rpcInfos['Login'];
		$this->nickname = Formatter::stripCodesWithoutColors($rpcInfos['NickName']); //TODO don't remove $s, $i
		$this->path = $rpcInfos['Path'];
		$this->language = $rpcInfos['Language'];
		$this->avatar = $rpcInfos['Avatar']['FileName'];
		$this->allies = $rpcInfos['Allies'];
		$this->clubLink = $rpcInfos['ClubLink'];
		$this->teamId = $rpcInfos['TeamId'];
		$this->isSpectator = $rpcInfos['IsSpectator'];
		$this->isOfficial = $rpcInfos['IsInOfficialMode'];
		$this->isReferee = $rpcInfos['IsReferee'];
		$this->ladderScore = $rpcInfos['LadderStats']['PlayerRankings'][0]['Score'];
		$this->ladderRank = $rpcInfos['LadderStats']['PlayerRankings'][0]['Ranking'];

		$this->ipAddress = $rpcInfos['IPAddress'];

		$this->joinTime = time();

	}


	/**
	 * Check if player is not a real player
	 *
	 * @return bool
	 */
	public function isFakePlayer() {
		return ($this->pid <= 0 || $this->path == "");
	}


	/**
	 * Get province
	 *
	 * @return string
	 */
	public function getProvince() {
		$pathParts = explode('|', $this->path);
		if (isset($pathParts[3])) {
			return $pathParts[3];
		}
		return $this->getCountry();
	}

	/**
	 * Get country
	 *
	 * @return string
	 */
	public function getCountry() {
		$pathParts = explode('|', $this->path);
		if (isset($pathParts[2])) {
			return $pathParts[2];
		}
		if (isset($pathParts[1])) {
			return $pathParts[1];
		}
		if (isset($pathParts[0])) {
			return $pathParts[0];
		}
		return $this->path;
	}

	/**
	 * Get continent
	 *
	 * @return string
	 */
	public function getContinent() {
		$pathParts = explode('|', $this->path);
		if (isset($pathParts[1])) {
			return $pathParts[1];
		}
		if (isset($pathParts[0])) {
			return $pathParts[0];
		}
		return $this->path;
	}
}
