<?php

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

	const XMLRPC_CALLBACKSLIST     = 'XmlRpc.CallbacksList';
	const XMLRPC_ENABLEDCALLBACKS  = 'XmlRpc.CallbacksList_Enabled';
	const XMLRPC_DISABLEDCALLBACKS = 'XmlRpc.CallbacksList_Disabled';
	const XMLRPC_CALLBACKHELP      = 'XmlRpc.CallbackHelp';
	const XMLRPC_METHODSLIST       = 'XmlRpc.MethodsList';
	const XMLRPC_METHODHELP        = 'XmlRpc.MethodHelp';
	const XMLRPC_DOCUMENTATION     = 'XmlRpc.Documentation';
	const XMLRPC_APIVERSION        = 'XmlRpc.ApiVersion';
	const XMLRPC_ALLAPIVERSIONS    = 'XmlRpc.AllApiVersions';

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

	const MP_WARMUP_START  = 'Maniaplanet.WarmUp.Start';
	const MP_WARMUP_END    = 'Maniaplanet.WarmUp.End';
	const MP_WARMUP_STATUS = 'Maniaplanet.WarmUp.Status';

	const MP_USES_TEAMMODE = 'Maniaplanet.Mode.UseTeams';
	const MP_PAUSE_STATUS  = 'Maniaplanet.Pause.Status';

	const SM_UIPROPERTIES = 'Shootmania.UI.Properties';
	const SM_SCORES       = "Shootmania.Scores";

	const SM_ONEVENTDEFAULT = "Shootmania.Event.Default";
	const SM_ONSHOOT        = "Shootmania.Event.OnShoot";
	const SM_ONHIT          = "Shootmania.Event.OnHit";
	const SM_ONNEARMISS     = "Shootmania.Event.OnNearMiss";
	const SM_ONARMOREMPTY   = "Shootmania.Event.OnArmorEmpty";
	const SM_ONCAPTURE      = "Shootmania.Event.OnCapture";
	const SM_ONSHOTDENY     = "Shootmania.Event.OnShotDeny";
	const SM_ONFALLDAMAGE   = "Shootmania.Event.OnFallDamage";
	const SM_ONCOMMAND      = "Shootmania.Event.OnCommand";

	/**
	 * Use the PlayerManager Callback in favour of this
	 *
	 * @see \ManiaControl\Players\PlayerManager::CB_PLAYERCONNECT
	 */
	const SM_ONPLAYERADDED = "Shootmania.Event.OnPlayerAdded";

	/**
	 * Use the PlayerManager Callback in favour of this
	 *
	 * @see \ManiaControl\Players\PlayerManager::CB_PLAYERDISCONNECT
	 */
	const SM_ONPLAYERREMOVED = "Shootmania.Event.OnPlayerRemoved";

	const SM_ONPLAYERREQUESTRESPAWN      = "Shootmania.Event.OnPlayerRequestRespawn";
	const SM_ONACTIONCUSTOMEVENT         = "Shootmania.Event.OnActionCustomEvent";
	const SM_ONACTIONEVENT               = "Shootmania.Event.OnActionEvent";
	const SM_ONPLAYERTOUCHESOBJECT       = "Shootmania.Event.OnPlayerTouchesObject";
	const SM_ONPLAYERTRIGGERSSECTOR      = "Shootmania.Event.OnPlayerTriggersSector";
	const SM_ONPLAYERTHROWSOBJECT        = "Shootmania.Event.OnPlayerThrowsObject";
	const SM_ONPLAYERREQUESTACTIONCHANGE = "Shootmania.Event.OnPlayerRequestActionChange";

	//SM GameMode Callbacks
	const SM_ELITE_STARTTURN       = 'Shootmania.Elite.StartTurn';
	const SM_ELITE_ENDTURN         = 'Shootmania.Elite.EndTurn';
	const SM_JOUST_ONRELOAD        = 'Shootmania.Joust.OnReload';
	const SM_JOUST_SELECTEDPLAYERS = 'Shootmania.Joust.SelectedPlayers';
	const SM_JOUST_ROUNDRESULT     = 'Shootmania.Joust.RoundResult';
	const SM_ROYAL_POINTS          = 'Shootmania.Royal.Points';
	const SM_ROYAL_PLAYERSPAWN     = 'Shootmania.Royal.PlayerSpawn';
	const SM_ROYAL_ROUNDWINNER     = 'Shootmania.Royal.RoundWinner';

	// New TM Callbacks
	const TM_ONEVENTDEFAULT   = "Trackmania.Event.Default";
	const TM_ONEVENTSTARTLINE = "Trackmania.Event.StartLine";
	const TM_ONCOMMAND        = "Trackmania.Event.OnCommand";

	/**
	 * Use the PlayerManager Callback in favour of this
	 *
	 * @see \ManiaControl\Players\PlayerManager::CB_PLAYERCONNECT
	 */
	const TM_ONPLAYERADDED = "Trackmania.Event.OnPlayerAdded";

	/**
	 * Use the PlayerManager Callback in favour of this
	 *
	 * @see \ManiaControl\Players\PlayerManager::CB_PLAYERDISCONNECT
	 */
	const TM_ONPLAYERREMOVED = "Trackmania.Event.OnPlayerRemoved";

	const TM_ONWAYPOINT       = "Trackmania.Event.WayPoint";
	const TM_ONGIVEUP         = "Trackmania.Event.GiveUp";
	const TM_ONRESPAWN        = "Trackmania.Event.Respawn";
	const TM_ONSTUNT          = "Trackmania.Event.Stunt";
	const TM_ONSTARTCOUNTDOWN = "Trackmania.Event.StartCountdown";
	const TM_SCORES           = "Trackmania.Scores";
	const TM_WARMUPSTART      = "Trackmania.WarmUp.Start";
	const TM_WARMUPSTARTROUND = "Trackmania.WarmUp.StartRound";
	const TM_WARMUPENDROUND   = "Trackmania.WarmUp.EndRound";
	const TM_WARMUPEND        = "Trackmania.WarmUp.End";

	const TM_UIPROPERTIES = 'Trackmania.UI.Properties';

	const TM_POINTSREPARTITION = 'Trackmania.PointsRepartition';

	//ManiaControl Callbacks
	/** BeginMap Callback: Map */
	const BEGINMAP = 'Callbacks.BeginMap';
	/** EndMap Callback: Map*/
	const ENDMAP = 'Callbacks.EndMap';

	//OLD Callbacks
	/** BeginMatch Callback: MatchNumber
	 *
	 * @deprecated
	 */
	const BEGINMATCH = 'Callbacks.BeginMatch';
	/** LoadingMap Callback: MapNumber
	 *
	 * @deprecated
	 */
	const LOADINGMAP = 'Callbacks.LoadingMap';

	/** BeginSubMatch Callback: SubmatchNumber
	 *
	 * @deprecated
	 */
	const BEGINSUBMATCH = 'Callbacks.BeginSubmatch';
	/** BeginRound Callback: RoundNumber
	 *
	 * @deprecated
	 */
	const BEGINROUND = 'Callbacks.BeginRound';
	/** BeginTurn Callback: TurnNumber
	 *
	 * @deprecated
	 */
	const BEGINTURN = 'Callbacks.BeginTurn';
	/** BeginTurnStop Callback: TurnNumber
	 *
	 * @deprecated
	 */
	const BEGINTURNSTOP = 'Callbacks.BeginTurnStop';
	/** BeginPlaying Callback
	 *
	 * @deprecated
	 */
	const BEGINPLAYING = 'Callbacks.BeginPlaying';
	/** EndPlaying Callback
	 *
	 * @deprecated
	 */
	const ENDPLAYING = 'Callbacks.EndPlaying';
	/** EndTurn Callback: TurnNumber
	 *
	 * @deprecated
	 */
	const ENDTURN = 'Callbacks.EndTurn';
	/** EndTurnStop Callback: TurnNumber
	 *
	 * @deprecated
	 */
	const ENDTURNSTOP = 'Callbacks.EndTurnStop';
	/** EndRound Callback: RoundNumber
	 *
	 * @deprecated
	 */
	const ENDROUND = 'Callbacks.EndRound';
	/** EndRound Callback: RoundNumber
	 *
	 * @deprecated
	 */
	const ENDROUNDSTOP = 'Callbacks.EndRoundStop';
	/** EndSubmatch Callback: SubmatchNumber
	 *
	 * @deprecated
	 */
	const ENDSUBMATCH = 'Callbacks.EndSubmatch';

	/** BeginPodium Callback
	 *
	 * @deprecated
	 */
	const BEGINPODIUM = 'Callbacks.BeginPodium';
	/** EndPodium Callback
	 *
	 * @deprecated
	 */
	const ENDPODIUM = 'Callbacks.EndPodium';
	/** UnloadingMap Callback
	 *
	 * @deprecated
	 */
	const UNLOADINGMAP = 'Callbacks.UnloadingMap';
	/** EndMatch Callback: MatchNumber
	 *
	 * @deprecated
	 */
	const ENDMATCH = 'Callbacks.EndMatch';

	/** BeginWarmup Callback
	 *
	 * @deprecated
	 */
	const BEGINWARMUP = 'Callbacks.BeginWarmUp';
	/** EndWarmup Callback
	 *
	 * @deprecated
	 */
	const ENDWARMUP = 'Callbacks.EndWarmUp';

	/** Scores Callback (returned after LibXmlRpc_PlayerRanking): Scores
	 *
	 * @deprecated
	 */
	const SCORESREADY = 'Callbacks.ScoresReady';

	/** Scores Callback (returned after LibXmlRpc_PlayerRanking in SM, or LibXmlRpc_TeamsScores in Trackmania): Scores
	 *
	 * @deprecated
	 */
	const SCORES = 'Callbacks.Scores';

	/** Rankings Callback
	 *
	 * @deprecated
	 */
	const RANKINGS = 'Callbacks.Rankings';

	/** PlayerRanking Callback, returned after LibXmlRpc_PlayerRanking
	 * try to avoid to use this, just use the Get function of the RankingsManager instead
	 * param1 Player $player
	 * param2 int $rank
	 * param3 int $currentPoints
	 * param4 int AFKStatus
	 *
	 * @deprecated
	 */
	const PLAYERRANKING = 'Callbacks.PlayerRanking';

	/*
	 * ShootMania Callbacks
	 */
	/** RankingsUpdated Callback: SortedRankings
	 *
	 * @deprecated
	 */
	const RANKINGSUPDATED = 'Callbacks.RankingsUpdated';

	/** Returns the AFKStatus of an Player, returned after  param1 Scores
	 *
	 * @deprecated
	 */
	const AFKSTATUS = 'Callbacks.AfkStatus';
	/** Returns if the GameMode has Warmup activated, returned after  param1 Scores
	 *
	 * @deprecated
	 */
	const WARMUPSTATUS = 'Callbacks.WarmupStatus';

	/** OnShoot Callback: Player, WeaponNumber (see Weapons Structure)
	 *
	 * @deprecated
	 */
	const ONSHOOT = 'Callbacks.OnShoot';

	/** OnHit Callback: PlayerHitStructure
	 *
	 * @deprecated
	 */
	const ONHIT = 'Callbacks.OnHit';
	/** OnNearMiss Callback: NearMissStructure
	 *
	 * @deprecated
	 */
	const ONNEARMISS = 'Callbacks.OnNearMiss';
	/** OnArmorEmpty Callback: ArmorEmptyStructure
	 *
	 * @deprecated
	 */
	const ONARMOREMPTY = 'Callbacks.OnArmorEmpty';
	/** OnCapture Callback: CaptureStructure
	 *
	 * @deprecated
	 */
	const ONCAPTURE = 'Callbacks.OnCapture';
	/** OnPlayerRequestRespawn Callback: Player
	 *
	 * @deprecated
	 */
	const ONPLAYERREQUESTRESPAWN = 'Callbacks.OnPlayerRequestRespawn';

	/*
	 * TrackMania Callbacks
	 */
	/** OnStartLine Callback
	 *
	 * @deprecated
	 */
	const ONSTARTLINE = 'Callbacks.StartLine';
	/** OnWayPoint Callback
	 *
	 * @deprecated
	 */
	const ONWAYPOINT = 'Callbacks.WayPoint';
	/** OnGiveUp Callback
	 *
	 * @deprecated
	 */
	const ONGIVEUP = 'Callbacks.GiveUp';
	/** OnRespawn Callback
	 *
	 * @deprecated
	 */
	const ONRESPAWN = 'Callbacks.Respawn';
	/** OnStunt Callback
	 *
	 * @deprecated
	 */
	const ONSTUNT = 'Callbacks.Stunt';

}
