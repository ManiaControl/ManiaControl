<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

// TODO XMLRPCLib: remettre les credits

namespace Maniaplanet\DedicatedServer\Xmlrpc;

if (!defined('LF')) define('LF', "\n");

class ClientMulticall extends Client
{
	public $calls = array();

	function addCall($methodName, $args)
	{
		$struct = array('methodName' => $methodName, 'params' => $args);
		$this->calls[] = $struct;

		return (count($this->calls) - 1);
	}
	
	function multiquery()
	{
		$result = array();
		if(count($this->calls))
		{
			$result = parent::query('system.multicall', $this->calls);
			$this->calls = array();  // reset for next calls
		}
		return $result;
	}

	function multiqueryIgnoreResult()
	{
		if(count($this->calls))
		{
			parent::queryIgnoreResult('system.multicall', $this->calls);
			$this->calls = array();  // reset for next calls
		}
	}
}

?>