<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer\Structures;

class ScriptInfo extends AbstractStructure
{

	public $name;
	public $compatibleMapTypes;
	public $description;
	public $version;
	public $paramDescs = array();
	public $commandDescs = array();

	static public function fromArray($array)
	{
		$object = parent::fromArray($array);

		if($object->paramDescs)
		{
			$object->paramDescs = ScriptSettings::fromArrayOfArray($object->paramDescs);
		}
		if($object->commandDescs)
		{
			$object->commandDescs = Command::fromArrayOfArray($object->commandDescs);
		}
		return $object;
	}

}

?>