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

	static public function fromArray($array)
	{
		$object = parent::fromArray($array);

		if($object->paramDescs)
		{
			$object->paramDescs = ScriptSettings::fromArrayOfArray($object->paramDescs);
		}
		return $object;
	}

}

?>