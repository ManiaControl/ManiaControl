<?php
namespace ManiaControl\Callbacks;
	//TODO method class for all the libxmlrpc get Methods, to fetch the callback asnyc
/**
 * Callbacks Interface
 *
 * @author    steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Callbacks {
	//Common Callbacks
	const SCRIPTCALLBACK = 'Callbacks.ScriptCallback';
	/** BeginMatch Callback, param1 MatchNumber */
	const BEGINMATCH = "Callbacks.BeginMatch";
	/** LoadingMap Callback, Number of Map */
	const LOADINGMAP = "Callbacks.LoadingMap";
	/** BeginMap Callback, triggered by MapManager, param1 Map Object */
	const BEGINMAP = "Callbacks.BeginMap";
	/** BeginSubMatch Callback, param1 Number of Submatch */
	const BEGINSUBMATCH = "Callbacks.BeginSubmatch";
	/** BeginRound Callback, param1 Number of Round */
	const BEGINROUND = "Callbacks.BeginRound";
	/** BeginTurn Callback, param1 Number of Turn */
	const BEGINTURN = "Callbacks.BeginTurn";
	/** EndTurn Callback, param1 Number of Turn */
	const ENDTURN = "Callbacks.EndTurn";
	/** EndRound Callback, param1 Number of Round */
	const ENDROUND = "Callbacks.EndRound";
	/** EndSubMatch Callback, param1 Number of Submatch */
	const ENDSUBMATCH = "Callbacks.EndSubmatch";
	/** BeginMap Callback, triggered by MapManager, param1 Map Object */
	const ENDMAP = "Callbacks.EndMap";
	/** EndMatch Callback, param1 MatchNumber */
	const ENDMATCH = "Callbacks.EndMatch";
	/** BeginWarmup Callback, no parameters */
	const BEGINWARMUP = "Callbacks.BeginWarmUp";
	/** EndWarmup Callback, no parameters */
	const ENDWARMUP = "Callbacks.EndWarmUp";
	/** PlayerRanking callback, returned after LibXmlRpc_PlayerRanking
	 * try to avoid to use this, just use the Get function of the RankingsManager instead
	 * param1 Player $player
	 * param2 int $rank
	 * param3 int $currentPoints
	 * param4 int AFKStatus */
	const PLAYERRANKING = 'Callbacks.PlayerRanking';

	//ShootMania Callbacks
	/** RankingsUpdated Callback, param1 Sorted Rankings */
	const RANKINGSUPDATED = 'Callbacks.RankingsUpdated';
	/** RankingsUpdated Callback, returned after LibXmlRpc_PlayerRanking param1 Scores */
	const SCORES = 'Callbacks.Scores';
	/** Returns the AFKStatus of an Player, returned after  param1 Scores */ //returned after TODO
	const AFKSTATUS = 'Callbacks.AfkStatus';
	/** Returns if the GameMode has Warmup activated, returned after  param1 Scores */ //returned after TODO
	const WARMUPSTATUS = 'Callbacks.WarmupStatus';
} 