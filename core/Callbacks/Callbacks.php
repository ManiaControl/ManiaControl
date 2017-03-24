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

	const XMLRPC_CALLBACKSLIST  = 'Callbacks.XmlRpcCallbacksList';
	const MP_STARTSERVERSTART   = 'Callbacks.ManiaPlanetStartServerStart';
	const MP_STARTSERVEREND     = 'Callbacks.ManiaPlanetStartServerEnd';
	const MP_STARTMATCHSTART    = 'Callbacks.ManiaPlanetStartMatchStart';
	const MP_STARTMATCHEND      = 'Callbacks.ManiaPlanetStartMatchEnd';
	//const MP_STARTMAPSTART      = 'Callbacks.ManiaPlanetStartMapStart';
	//const MP_STARTMAPEND        = 'Callbacks.ManiaPlanetStartMapEnd';
	const MP_STARTROUNDSTART    = 'Callbacks.ManiaPlanetStartRoundStart';
	const MP_STARTROUNDEND      = 'Callbacks.ManiaPlanetStartRoundEnd';
	const MP_STARTTURNSTART     = 'Callbacks.ManiaPlanetStartTurnStart';
	const MP_STARTTURNEND       = 'Callbacks.ManiaPlanetStartTurnEnd';
	const MP_STARTPLAYLOOPSTART = 'Callbacks.ManiaPlanetStartPlayLoopStart';
	const MP_STARTPLAYLOOPEND   = 'Callbacks.ManiaPlanetStartPlayLoopEnd';
	const MP_ENDTURNSTART      = 'Callbacks.ManiaPlanetEndTurnStart';
	const MP_ENDTURNEND         = 'Callbacks.ManiaPlanetEndTurnEnd';
	const MP_ENDROUNDSTART      = 'Callbacks.ManiaPlanetEndRoundStart';
	const MP_ENDROUNDEND        = 'Callbacks.ManiaPlanetEndRoundEnd';
	const MP_ENDMAPSTART        = 'Callbacks.ManiaPlanetEndMapStart';
	const MP_ENDMAPEND          = 'Callbacks.ManiaPlanetEndMapEnd';
	const MP_ENDMATCHSTART      = 'Callbacks.ManiaPlanetEndMatchStart';
	const MP_ENDMATCHEND        = 'Callbacks.ManiaPlanetEndMatchEnd';
	const MP_ENDSERVERSTART     = 'Callbacks.ManiaPlanetEndServerStart';
	const MP_ENDSERVEREND       = 'Callbacks.ManiaPlanetEndServerEnd';
	const MP_LOADINGMAPSTART    = 'Callbacks.ManiaPlanetLoadingMapStart';
	const MP_LOADINGMAPEND      = 'Callbacks.ManiaPlanetLoadingMapEnd';
	const MP_UNLOADINGMAPSTART  = 'Callbacks.ManiaPlanetUnLoadingMapStart';
	const MP_UNLOADINGMAPEND    = 'Callbacks.ManiaPlanetUnLoadingMapEnd';
	const MP_PODIUMSTART        = 'Callbacks.ManiaPlanetPodiumStart';
	const MP_PODIUMEND          = 'Callbacks.ManiaPlanetPodiumEnd';

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
