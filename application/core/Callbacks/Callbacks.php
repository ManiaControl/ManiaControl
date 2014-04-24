<?php
namespace ManiaControl\Callbacks;

/**
 * Callbacks Interface
 *
 * @author    steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Callbacks {
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
} 