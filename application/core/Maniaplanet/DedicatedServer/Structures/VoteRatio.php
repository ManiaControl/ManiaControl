<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */
 
namespace Maniaplanet\DedicatedServer\Structures;

class VoteRatio extends AbstractStructure
{
	const COMMAND_SCRIPT_SETTINGS = 'SetModeScriptSettingsAndCommands';
	const COMMAND_NEXT_MAP = 'NextMap';
	const COMMAND_JUMP_MAP = 'JumpToMapIndex';
	const COMMAND_SET_NEXT_MAP = 'SetNextMapIndex';
	const COMMAND_RESTART_MAP = 'RestartMap';
	const COMMAND_TEAM_BALANCE = 'AutoTeamBalance';
	const COMMAND_KICK = 'Kick';
	const COMMAND_BAN = 'Ban';
	
	public $command;
	public $param;
	public $ratio;

	public function __construct($command = null, $ratio = null)
	{
		$this->command = $command;
		$this->ratio = $ratio;
	}
}
?>