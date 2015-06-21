<?php

namespace ManiaControl\Sockets;


interface SocketMethods {
	/** Returns the last 200 lines of the chat (inclusive player logins and nicknames) */
	const GET_SERVER_CHAT = "Chat.GetServerChat";
}