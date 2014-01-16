<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer\Xmlrpc;

class Base64 
{
	public $data;

	function __construct($data)
	{
		$this->data = $data;
	}

	function getXml() 
	{
		return '<base64>'.base64_encode($this->data).'</base64>';
	}
}

?>