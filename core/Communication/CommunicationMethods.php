<?php

namespace ManiaControl\Communication;

/**
 * Communication Methods Interface
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2015 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface CommunicationMethods {
	/** Restarts Mania Control
	 *  Optional Params
	 *  - message
	 */
	const RESTART_MANIA_CONTROL = "ManiaControl.Restart";


	/** Restarts the Current Map
	 *  no Parameters
	 */
	const RESTART_MAP = "MapActions.RestartMap";

	/** Skips the Current Map
	 *  no Parameters
	 */
	const SKIP_MAP = "MapActions.SkipMap";

	/** Skips to a Specific Map by MxId or MapUid
	 *  Required Parameters
	 *   - mxId (integer)
	 *   OR
	 *   - mapUid (string)
	 */
	const SKIP_TO_MAP = "MapActions.SkipToMap";

	/** Adds a Map from Mania Exchange to the Server
	 *  Required Parameters
	 *  - mxId (integer)
	 * (no success returning yet because of asynchronously of adding)
	 */
	const ADD_MAP = "MapManager.AddMap";

	/** Removes a Map from the Server
	 *  Required Parameters
	 *  - mapUid (string)
	 *  Optional Parameters
	 *  - displayMessage (default true)
	 *  - eraseMapFile   (default false)
	 */
	const REMOVE_MAP = "MapManager.RemoveMap";

	/** Updates a Map over Mania Exchange
	 *  Required Parameters
	 *  - mapUid
	 * (no success returning yet because of asynchronously of adding)
	 */
	const UPDATE_MAP = "MapManager.UpdateMap";

	/** Gets the current Map
	 *  Required Parameters
	 *   - mxId (integer)
	 *   OR
	 *   - mapUid (string)
	 */
	const GET_CURRENT_MAP = "MapManager.GetCurrentMap";

	/** Gets the specific Map
	 *  no Parameters
	 */
	const GET_MAP = "MapManager.GetMap";

	/** Gets the current Map List
	 *  no Parameters
	 */
	const GET_MAP_LIST = "MapManager.GetMapList";

	/** Gets Mania Control PlayerList
	 *  no Parameters
	 */
	const GET_PLAYER_LIST = "PlayerManager.GetPlayerList";

	/** Warns a Player
	 *  Required Params
	 *  - login
	 */
	const WARN_PLAYER = "PlayerActions.WarnPlayer";

	/** Mutes a Player
	 *  Required Params
	 *  - login
	 */
	const MUTE_PLAYER = "PlayerActions.MutePlayer";

	/** UnMutes a Player
	 *  Required Params
	 *  - login
	 */
	const UNMUTE_PLAYER = "PlayerActions.UnMutePlayer";

	/** UnMutes a Player
	 *  Required Params
	 *  - login
	 *  Optional Params
	 *  - message
	 */
	const KICK_PLAYER = "PlayerActions.KickPlayer";

	/** Forces a player to Spectator
	 *  Required Params
	 *  - login
	 */
	const FORCE_PLAYER_TO_SPEC = "PlayerActions.ForcePlayerToSpec";

	/** Forces a player to Spectator
	 *  Required Params
	 *  - login
	 *  Optional Params
	 *  - teamId (integer, id of the team the player should get forced into it)
	 */
	const FORCE_PLAYER_TO_PLAY = "PlayerActions.ForcePlayerToPlay";

	/** Returns the last 200 lines of the chat (inclusive player logins and nicknames)
	 * No Params
	 */
	const GET_SERVER_CHAT = "Chat.GetServerChat";

	/** Sends a ChatMessage to the Server
	 *  Required Params:
	 *  - message
	 *  Optional Params
	 *  - prefix        (use custom prefix or false for no prefix)
	 *  - login         (login of a receiver if the message don't get sent to all)
	 *  - adminLevel    (minimum Admin Level if the Message should get sent to an Admin)
	 *  - type          (type of the message (information, error, success or usage)
	 */
	const SEND_CHAT_MESSAGE = "Chat.SendChatMessage";
}