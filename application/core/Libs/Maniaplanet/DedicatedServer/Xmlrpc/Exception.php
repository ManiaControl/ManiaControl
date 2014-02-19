<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer\Xmlrpc;

class Exception extends \Exception 
{
	const ANWSER_TOO_BIG = 1;
	const REQUEST_TOO_BIG = 2;
	const OTHER = 999;
}

?>