<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */
namespace Maniaplanet\DedicatedServer\Structures;

class Player extends AbstractStructure
{
	public $playerId;
	public $login;
	public $nickName;
	public $teamId;
	public $path;
	public $language;
	public $clientVersion;
	public $clientName;
	public $iPAddress;
	public $downloadRate;
	public $uploadRate;
	public $isSpectator;
	public $isInOfficialMode;
	public $avatar;
	public $skins;
	public $ladderStats;
	public $hoursSinceZoneInscription;
	public $onlineRights;
	public $rank;
	public $bestTime;
	public $bestCheckpoints;
	public $score;
	public $nbrLapsFinished;
	public $ladderScore;
	public $stateUpdateLatency;
	public $stateUpdatePeriod;
	public $latestNetworkActivity;
	public $packetLossRate;
	public $spectatorStatus;
	public $ladderRanking;
	public $flags;
	public $isConnected = true;
	public $allies = array();
	public $clubLink;

	//Flags details
	public $forceSpectator;
	public $isReferee;
	public $isPodiumReady;
	public $isUsingStereoscopy;
	public $isManagedByAnOtherServer;
	public $isServer;
	public $hasPlayerSlot;
	public $isBroadcasting;
	public $hasJoinedGame;

	//SpectatorStatus details
	public $spectator;
	public $temporarySpectator;
	public $pureSpectator;
	public $autoTarget;
	public $currentTargetId;

	function getArrayFromPath()
	{
		return explode('|', $this->path);
	}

	/**
	 * @return Player
	 */
	static public function fromArray($array)
	{
		$object = parent::fromArray($array);

		$object->skins = Skin::fromArrayOfArray($object->skins);
		//Detail flags
		$object->forceSpectator = $object->flags % 10; // 0, 1 or 2
		$object->isReferee = (bool) (intval($object->flags / 10) % 10);
		$object->isPodiumReady = (bool) (intval($object->flags / 100) % 10);
		$object->isUsingStereoscopy = (bool) (intval($object->flags / 1000) % 10);
		$object->isManagedByAnOtherServer = (bool) (intval($object->flags / 10000) % 10);
		$object->isServer = (bool) (intval($object->flags / 100000) % 10);
		$object->hasPlayerSlot = (bool) (intval($object->flags / 1000000) % 10);
		$object->isBroadcasting = (bool) (intval($object->flags / 10000000) % 10);
		$object->hasJoinedGame = (bool) (intval($object->flags / 100000000) % 10);
		//Details spectatorStatus
		$object->spectator = (bool) ($object->spectatorStatus % 10);
		$object->temporarySpectator = (bool) (intval($object->spectatorStatus / 10) % 10);
		$object->pureSpectator = (bool) (intval($object->spectatorStatus / 100) % 10);
		$object->autoTarget = (bool) (intval($object->spectatorStatus / 1000) % 10);
		$object->currentTargetId = intval($object->spectatorStatus / 10000);

		return $object;
	}
}
?>