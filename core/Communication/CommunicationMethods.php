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
	/** Returns the last 200 lines of the chat (inclusive player logins and nicknames) */
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