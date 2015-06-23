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

	//TODO implement
	const FORCE_PLAYER_TO_SPEC = "PlayerActions.ForcePlayerToSpec";
	//TODO implement
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