<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */
namespace Maniaplanet\DedicatedServer\Structures;

class LobbyInfo extends AbstractStructure
{
	public $isLobby;
	public $lobbyPlayers;
	public $lobbyMaxPlayers;
	public $lobbyPlayersLevel;
}

?>