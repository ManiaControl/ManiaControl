<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */
 
namespace Maniaplanet\DedicatedServer\Structures;

class Vote extends AbstractStructure
{
	const STATE_NEW = 'NewVote';
	const STATE_CANCELLED = 'VoteCancelled';
	const STATE_PASSED = 'VotePassed';
	const STATE_FAILED = 'VoteFailed';
	
	public $status;
	public $callerLogin;
	public $cmdName;
	public $cmdParam;
}
?>