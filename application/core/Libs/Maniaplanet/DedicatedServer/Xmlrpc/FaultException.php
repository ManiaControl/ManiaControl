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
		}

		return new self($faultString, $faultCode);
	}
}

class LoginUnknownException extends FaultException {}
class CouldNotWritePlaylistFileException extends FaultException {}
class StartIndexOutOfBoundException extends FaultException {}
class NotInScriptModeException extends FaultException {}
?>
