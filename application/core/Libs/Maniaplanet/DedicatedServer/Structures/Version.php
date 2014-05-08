<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */
 
namespace Maniaplanet\DedicatedServer\Structures;

class Version extends AbstractStructure
{
	public $name;
	public $titleId;
	public $version;
	public $build;
	public $apiVersion;
}
?>