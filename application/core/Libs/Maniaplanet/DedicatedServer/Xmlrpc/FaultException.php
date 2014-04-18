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
		}

		return new self($faultString, $faultCode);
	}
}

class LoginUnknownException extends FaultException {}

?>
