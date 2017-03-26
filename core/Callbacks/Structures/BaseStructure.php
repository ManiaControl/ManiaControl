<?php

namespace ManiaControl\Callbacks\Structures;


use ManiaControl\ManiaControl;
use ReflectionClass;

/**
 * Base Structure of all Callback Structures
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class BaseStructure {
	/** @var ManiaControl $maniaControl */
	protected $maniaControl;
	private   $plainJsonObject;

	protected function __construct(ManiaControl $maniaControl, $data) {
		$this->maniaControl    = $maniaControl;
		$this->plainJsonObject = json_decode($data[0]);
	}

	/**
	 * Gets the Plain Json
	 */
	public function getPlainJsonObject() {
		return $this->plainJsonObject;
	}

	/**
	 * Gets Information about the Class, and a List of the Public Method
	 */
	public function getUsage() {
		$reflection = new ReflectionClass(get_class($this));
		echo $reflection->getDocComment();

		echo "\nStructure Name of Class = " . get_class($this);

		echo "\nMethods:\n";

		$metody  = $reflection->getMethods();
		$methods = array_reverse($metody);
		foreach ($methods as $key => $value) {
			/** @var \ReflectionMethod $value */
			//Don't print the Constructor
			if ($value->isPublic() && $value->getName() != "__construct" && $value->getName() != "getUsage") {
				echo "\n\n";
				$txt = preg_replace('/\t/', '', $value->getDocComment());

				echo $txt;
				echo "\n \$result = " . $value->getName() . "();";
				$parameters = $value->getParameters();

				foreach ($parameters as $parameter) {
					echo "\n" . $parameter;
				}
				echo "\n";
			}
		}
	}
}