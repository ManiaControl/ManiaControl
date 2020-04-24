<?php

namespace ManiaControl\Callbacks;

/**
 * Callbacks Interface
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Callbacks {
	/*
	 * ManiaControl Callbacks
	 */
	const ONINIT     = 'Callbacks.OnInit';
	const AFTERINIT  = 'Callbacks.AfterInit';
	const ONSHUTDOWN = 'Callbacks.OnShutdown';
	/** @deprecated */
	const ONRESTART  = 'Callbacks.OnRestart';
	const ONREBOOT   = 'Callbacks.OnReboot';
	const PRELOOP    = 'Callbacks.PreLoop';
	const AFTERLOOP  = 'Callbacks.AfterLoop';

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

	const SM_UIPROPERTIES   = 'Shootmania.UI.Properties';
	const SM_SCORES         = "Shootmania.Scores";
	const SM_ONEVENTDEFAULT = "Shootmania.Event.Default";
	const SM_ONSHOOT        = "Shootmania.Event.OnShoot";
	const SM_ONHIT          = "Shootmania.Event.OnHit";
	const SM_ONNEARMISS     = "Shootmania.Event.OnNearMiss";
	const SM_ONARMOREMPTY   = "Shootmania.Event.OnArmorEmpty";
	const SM_ONCAPTURE      = "Shootmania.Event.OnCapture";
	const SM_ONSHOTDENY     = "Shootmania.Event.OnShotDeny";
	const SM_ONFALLDAMAGE   = "Shootmania.Event.OnFallDamage";
	const SM_ONCOMMAND      = "Shootmania.Event.OnCommand";

	const SM_PLAYERSAFK    = "Shootmania.AFK.IsAfk";
	const SM_AFKPROPERTIES = "Shootmania.AFK.GetProperties";

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
	const TM_ONPLAYERREMOVED   = "Trackmania.Event.OnPlayerRemoved";
	const TM_ONWAYPOINT        = "Trackmania.Event.WayPoint";
	const TM_ONGIVEUP          = "Trackmania.Event.GiveUp";
	const TM_ONRESPAWN         = "Trackmania.Event.Respawn";
	const TM_ONSTUNT           = "Trackmania.Event.Stunt";
	const TM_ONSTARTCOUNTDOWN  = "Trackmania.Event.StartCountdown";
	const TM_SCORES            = "Trackmania.Scores";
	const TM_WARMUPSTART       = "Trackmania.WarmUp.Start";
	const TM_WARMUPSTARTROUND  = "Trackmania.WarmUp.StartRound";
	const TM_WARMUPENDROUND    = "Trackmania.WarmUp.EndRound";
	const TM_WARMUPEND         = "Trackmania.WarmUp.End";
	const TM_UIPROPERTIES      = 'Trackmania.UI.Properties';
	const TM_POINTSREPARTITION = 'Trackmania.PointsRepartition';

	//ManiaControl Specific TM Callbacks
	const TM_ONFINISHLINE = "ManiaControl.Trackmania.Event.OnFinishLine";
	const TM_ONLAPFINISH  = "ManiaControl.Trackmania.Event.OnLapFinish";

	//ManiaControl Callbacks
	/** BeginMap Callback: Map */
	const BEGINMAP = 'Callbacks.BeginMap';
	/** EndMap Callback: Map*/
	const ENDMAP = 'Callbacks.EndMap';

}
