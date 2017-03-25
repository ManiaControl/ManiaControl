<?php

// TODO: method class for all the libxmlrpc get Methods, to fetch the callback asnyc
// TODO implement all STOP callbacks

// 22-3-2017 Added/Fixed TM Callback for WayPoint // Need to Add better checks eventually
namespace ManiaControl\Callbacks;

/**
 * Callbacks Interface
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Callbacks {
	/*
	 * ManiaControl Callbacks
	 */
	const ONINIT     = 'Callbacks.OnInit';
	const AFTERINIT  = 'Callbacks.AfterInit';
	const ONSHUTDOWN = 'Callbacks.OnShutdown';
	const ONRESTART  = 'Callbacks.OnRestart';

	/** Script Callback: CallbackName, CallbackData */
	const SCRIPTCALLBACK = 'Callbacks.ScriptCallback';

	/*
	 * Common Callbacks
	 */
	//NEW Callbacks

	const XMLRPC_CALLBACKSLIST = 'XmlRpc.CallbacksList';

	const MP_STARTSERVERSTART = 'Maniaplanet.StartServer_Start';
	const MP_STARTSERVEREND   = 'Maniaplanet.StartServer_End';
	const MP_STARTMATCHSTART  = 'Maniaplanet.StartMatch_Start';
	const MP_STARTMATCHEND    = 'Maniaplanet.StartMatch_End';
	//const MP_STARTMAPSTART      = 'Maniaplanet.StartMap_Start';
	//const MP_STARTMAPEND        = 'Maniaplanet.StartMap_End';
	const MP_STARTROUNDSTART = 'Maniaplanet.StartRound_Start';
	const MP_STARTROUNDEND   = 'Maniaplanet.StartRound_End';
	const MP_STARTTURNSTART  = 'Maniaplanet.StartTurn_Start';
	const MP_STARTTURNEND    = 'Maniaplanet.StartTurn_End';
	const MP_STARTPLAYLOOP   = 'Maniaplanet.StartPlayLoop';
	const MP_ENDPLAYLOOP     = 'Maniaplanet.EndPlayLoop';
	const MP_ENDTURNSTART    = 'Maniaplanet.EndTurn_Start';
	const MP_ENDTURNEND      = 'Maniaplanet.EndTurn_End';
	const MP_ENDROUNDSTART   = 'Maniaplanet.EndRound_Start';
	const MP_ENDROUNDEND     = 'Maniaplanet.EndRound_End';
	//const MP_ENDMAPSTART        = 'Maniaplanet.EndMap_Start';
	//const MP_ENDMAPEND          = 'Maniaplanet.EndMap_End';
	const MP_ENDMATCHSTART     = 'Maniaplanet.EndMatch_Start';
	const MP_ENDMATCHEND       = 'Maniaplanet.EndMatch_End';
	const MP_ENDSERVERSTART    = 'Maniaplanet.EndServer_Start';
	const MP_ENDSERVEREND      = 'Maniaplanet.EndServer_End';
	const MP_LOADINGMAPSTART   = 'Maniaplanet.LoadingMap_Start';
	const MP_LOADINGMAPEND     = 'Maniaplanet.LoadingMap_End';
	const MP_UNLOADINGMAPSTART = 'Maniaplanet.UnloadingMap_Start';
	const MP_UNLOADINGMAPEND   = 'Maniaplanet.UnloadingMap_End';
	const MP_PODIUMSTART       = 'Maniaplanet.Podium_Start';
	const MP_PODIUMEND         = 'Maniaplanet.Podium_End';

	const SM_SCORES = "Shootmania.Scores";

	const SM_EVENTDEFAULT = "Shootmania.Event.Default";
	const SM_ONSHOOT      = "Shootmania.Event.OnShoot";
	const SM_ONHIT        = "Shootmania.Event.OnHit";
	const SM_ONNEARMISS   = "Shootmania.Event.OnNearMiss";
	const SM_ONARMOREMPTY = "Shootmania.Event.OnArmorEmpty";
	const SM_ONCAPTURE    = "Shootmania.Event.OnCapture";
	const SM_ONSHOTDENY   = "Shootmania.Event.OnShotDeny";
	const SM_ONFALLDAMAGE = "Shootmania.Event.OnFallDamage";
	const SM_ONCOMMAND    = "Shootmania.Event.OnCommand";
	//Shootmania.Event.OnPlayerRemoved Shootmania.Event.OnPlayerAdded not needed yet
	const SM_ONPLAYERREQUESTRESAWPN      = "Shootmania.Event.OnPlayerRequestRespawn";
	const SM_ONACTIONCUSTOMEVENT         = "Shootmania.Event.OnActionCustomEvent";
	const SM_ONACTIONEVENT               = "Shootmania.Event.OnActionEvent";
	const SM_ONPLAYERTOUCHESOBJECT       = "Shootmania.Event.OnPlayerTouchesObject";
	const SM_ONPLAYERTRIGGERSSECTOR      = "Shootmania.Event.OnPlayerTriggersSector";
	const SM_ONPLAYERTHROWSOBJECT        = "Shootmania.Event.OnPlayerThrowsObject";
	const SM_ONPLAYERREQUESTACTIONCHANGE = "Shootmania.Event.OnPlayerRequestActionChange";


	const TM_EVENTDEFAULT = "Trackmania.Event.Default";

	//ManiaControl Callbacks
	/** BeginMap Callback: Map */
	const BEGINMAP = 'Callbacks.BeginMap';

	//OLD Callbacks
	/** BeginMatch Callback: MatchNumber */
	const BEGINMATCH = 'Callbacks.BeginMatch';
	/** LoadingMap Callback: MapNumber */
	const LOADINGMAP = 'Callbacks.LoadingMap';

	/** BeginSubMatch Callback: SubmatchNumber */
	const BEGINSUBMATCH = 'Callbacks.BeginSubmatch';
	/** BeginRound Callback: RoundNumber */
	const BEGINROUND = 'Callbacks.BeginRound';
	/** BeginTurn Callback: TurnNumber */
	const BEGINTURN = 'Callbacks.BeginTurn';
	/** BeginTurnStop Callback: TurnNumber */
	const BEGINTURNSTOP = 'Callbacks.BeginTurnStop';
	/** BeginPlaying Callback */
	const BEGINPLAYING = 'Callbacks.BeginPlaying';
	/** EndPlaying Callback */
	const ENDPLAYING = 'Callbacks.EndPlaying';
	/** EndTurn Callback: TurnNumber */
	const ENDTURN = 'Callbacks.EndTurn';
	/** EndTurnStop Callback: TurnNumber */
	const ENDTURNSTOP = 'Callbacks.EndTurnStop';
	/** EndRound Callback: RoundNumber */
	const ENDROUND = 'Callbacks.EndRound';
	/** EndRound Callback: RoundNumber */
	const ENDROUNDSTOP = 'Callbacks.EndRoundStop';
	/** EndSubmatch Callback: SubmatchNumber */
	const ENDSUBMATCH = 'Callbacks.EndSubmatch';
	/** EndMap Callback: Map */
	const ENDMAP = 'Callbacks.EndMap';
	/** BeginPodium Callback */
	const BEGINPODIUM = 'Callbacks.BeginPodium';
	/** EndPodium Callback */
	const ENDPODIUM = 'Callbacks.EndPodium';
	/** UnloadingMap Callback */
	const UNLOADINGMAP = 'Callbacks.UnloadingMap';
	/** EndMatch Callback: MatchNumber */
	const ENDMATCH = 'Callbacks.EndMatch';

	/** BeginWarmup Callback */
	const BEGINWARMUP = 'Callbacks.BeginWarmUp';
	/** EndWarmup Callback */
	const ENDWARMUP = 'Callbacks.EndWarmUp';

	/** Scores Callback (returned after LibXmlRpc_PlayerRanking): Scores */
	const SCORESREADY = 'Callbacks.ScoresReady';

	/** Scores Callback (returned after LibXmlRpc_PlayerRanking in SM, or LibXmlRpc_TeamsScores in Trackmania): Scores */
	const SCORES = 'Callbacks.Scores';

	/** Rankings Callback */
	const RANKINGS = 'Callbacks.Rankings';

	/** PlayerRanking Callback, returned after LibXmlRpc_PlayerRanking
	 * try to avoid to use this, just use the Get function of the RankingsManager instead
	 * param1 Player $player
	 * param2 int $rank
	 * param3 int $currentPoints
	 * param4 int AFKStatus */
	const PLAYERRANKING = 'Callbacks.PlayerRanking';

	/*
	 * ShootMania Callbacks
	 */
	/** RankingsUpdated Callback: SortedRankings */
	const RANKINGSUPDATED = 'Callbacks.RankingsUpdated';

	/** Returns the AFKStatus of an Player, returned after  param1 Scores */ //returned after TODO
	const AFKSTATUS = 'Callbacks.AfkStatus';
	/** Returns if the GameMode has Warmup activated, returned after  param1 Scores */ //returned after TODO
	const WARMUPSTATUS = 'Callbacks.WarmupStatus';

	/** OnShoot Callback: Player, WeaponNumber (see Weapons Structure) */
	const ONSHOOT = 'Callbacks.OnShoot';

	/** OnHit Callback: PlayerHitStructure */
	const ONHIT = 'Callbacks.OnHit';
	/** OnNearMiss Callback: NearMissStructure */
	const ONNEARMISS = 'Callbacks.OnNearMiss';
	/** OnArmorEmpty Callback: ArmorEmptyStructure */
	const ONARMOREMPTY = 'Callbacks.OnArmorEmpty';
	/** OnCapture Callback: CaptureStructure */
	const ONCAPTURE = 'Callbacks.OnCapture';
	/** OnPlayerRequestRespawn Callback: Player */
	const ONPLAYERREQUESTRESPAWN = 'Callbacks.OnPlayerRequestRespawn';

	/** Elite OnBeginTurn Callback: EliteBeginTurnStructure */
	const ELITE_ONBEGINTURN = "Callbacks.EliteOnBeginTurn";
	/** Elite OnEndTurn Callback: integer (VictoryTypes) */
	const ELITE_ONENDTURN = "Callbacks.EliteOnEndTurn";

	/** Joust Selected Players */
	const JOUST_SELECTEDPLAYERS = "Callbacks.JoustSelectedPlayers";

	/*
	 * TrackMania Callbacks
	 */
	/** OnStartLine Callback */
	const ONSTARTLINE = 'Callbacks.StartLine';
	/** OnWayPoint Callback */
	const ONWAYPOINT = 'Callbacks.WayPoint';
	/** OnGiveUp Callback */
	const ONGIVEUP = 'Callbacks.GiveUp';
	/** OnRespawn Callback */
	const ONRESPAWN = 'Callbacks.Respawn';
	/** OnStunt Callback */
	const ONSTUNT = 'Callbacks.Stunt';

}
