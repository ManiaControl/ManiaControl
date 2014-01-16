<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */
namespace Maniaplanet\DedicatedServer\Structures;

class SystemInfos extends AbstractStructure
{
	public $publishedIp;
	public $port;
	public $p2PPort;
	public $titleId;
	public $serverLogin;
	public $serverPlayerId;
	public $connectionDownloadRate;
    public $connectionUploadRate;
	public $isServer;
	public $isDedicated;
}
?>