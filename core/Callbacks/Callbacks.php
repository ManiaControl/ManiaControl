<?php

// TODO: method class for all the libxmlrpc get Methods, to fetch the callback asnyc
// TODO implement all STOP callbacks
namespace ManiaControl\Callbacks;

/**
 * Callbacks Interface
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2016 ManiaControl Team
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
	/** BeginMatch Callback: MatchNumber */
	const BEGINMATCH = 'Callbacks.BeginMatch';
	/** LoadingMap Callback: MapNumber */
	const LOADINGMAP = 'Callbacks.LoadingMap';
	/** BeginMap Callback: Map */
	const BEGINMAP = 'Callbacks.BeginMap';
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

	/*
	 * TrackMania Callbacks
	 */
	/** OnStartLine Callback */
	const ONSTARTLINE = 'Callbacks.OnStartLine';
	/** OnWayPoint Callback */
	const ONWAYPOINT = 'Callbacks.OnWayPoint';
	/** OnGiveUp Callback */
	const ONGIVEUP = 'Callbacks.OnGiveUp';
	/** OnRespawn Callback */
	const ONRESPAWN = 'Callbacks.OnRespawn';
	/** OnStunt Callback */
	const ONSTUNT = 'Callbacks.OnStunt';

}
