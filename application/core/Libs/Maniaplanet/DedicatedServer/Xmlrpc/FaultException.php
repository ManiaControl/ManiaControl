<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer\Xmlrpc;

class FaultException extends Exception
{
	static function create($faultString, $faultCode)
	{
		switch($faultString)
		{
			case 'Login unknown.':
				return new LoginUnknownException($faultString, $faultCode);
			case 'Unable to write the playlist file.':
				return new CouldNotWritePlaylistFileException($faultString, $faultCode);
			case 'Start index out of bound.':
				return new StartIndexOutOfBoundException($faultString, $faultCode);
			case 'Not in script mode.':
				return new NotInScriptModeException($faultString, $faultCode);
			case 'Change in progress.':
				return new ChangeInProgressException($faultString, $faultCode);
			case 'The player is not a spectator':
			case 'The player is not a spectator.':
				return new PlayerIsNotSpectatorException($faultString, $faultCode);
			case 'Player already ignored.':
				return new PlayerAlreadyIgnoredException($faultString, $faultCode);
			case 'Player not ignored.':
				return new PlayerNotIgnoredException($faultString, $faultCode);
			case 'Not in Team mode.':
				return new NotInTeamModeException($faultString, $faultCode);
			case 'The map isn\'t in the current selection.':
				return new MapNotInCurrentSelectionException($faultString, $faultCode);
			case 'Incompatible map type.':
			case 'Map not complete.':
				return new MapNotCompatibleOrCompleteException($faultString, $faultCode);
			case 'Ladder mode unknown.':
				return new LadderModeUnknownException($faultString, $faultCode);
		}

		return new self($faultString, $faultCode);
	}
}

class LoginUnknownException extends FaultException {}
class CouldNotWritePlaylistFileException extends FaultException {}
class StartIndexOutOfBoundException extends FaultException {}
class NotInScriptModeException extends FaultException {}
class ChangeInProgressException extends FaultException {}
class PlayerIsNotSpectatorException extends FaultException {}
class NotInTeamModeException extends FaultException {}
class MapNotInCurrentSelectionException extends FaultException{}
class MapNotCompatibleOrCompleteException extends FaultException{}
class LadderModeUnknownException extends FaultException{}
class PlayerAlreadyIgnoredException extends FaultException{}
class PlayerNotIgnoredException extends FaultException{}
