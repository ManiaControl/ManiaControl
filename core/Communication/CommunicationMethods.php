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
}