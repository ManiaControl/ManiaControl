<?php

namespace ManiaControl\General;

use ReflectionClass;

/**
 * Class DumpTrait Trait for Implementing the Methods for the UsageInformationAble Interface
 *
 * @package ManiaControl\General
 */
trait UsageInformationTrait {
	/**
	 * Gets Information about the Class, and a List of the Public Method
	 */
	public function getUsageInformation() {
		$reflection = new ReflectionClass(get_class($this));
		echo $reflection->getDocComment();

		echo "\nStructure Name of Class = " . get_class($this);
		echo "\n\nMethods:";

		$methods = array_reverse($reflection->getMethods());
		foreach ($methods as $key => $value) {
			/** @var \ReflectionMethod $value */
			//Don't print the Constructor
			if ($value->isPublic() && $value->getName() != "__construct" && $value->getName() != "getUsage") {
				echo "\n";
				echo preg_replace('/\t/', '', $value->getDocComment());
				echo "\n \$result = " . $value->getName() . "(); \n";
				$parameters = $value->getParameters();

				foreach ($parameters as $parameter) {
					echo $parameter . "\n";
				}
			}
		}
		echo "\n";
		//TODO add public Constands and Properties
	}
}