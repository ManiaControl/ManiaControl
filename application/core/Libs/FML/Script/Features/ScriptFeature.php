<?php

namespace FML\Script\Features;

use FML\Script\Script;
use FML\Types\ScriptFeatureable;

/**
 * ManiaLink Script Feature Class
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class ScriptFeature {
	/**
	 * Collect the Script Features of the given objects
	 *
	 * @param ScriptFeatureable $objects (optional) Various amount of ScriptFeatureable objects
	 * @return ScriptFeature[]
	 */
	public static function collect() {
		$params         = func_get_args();
		$scriptFeatures = array();
		foreach ($params as $object) {
			if ($object instanceof ScriptFeatureable) {
				$scriptFeatures = array_merge($scriptFeatures, $object->getScriptFeatures());
			} else if ($object instanceof ScriptFeature) {
				array_push($scriptFeatures, $object);
			}
		}
		return $scriptFeatures;
	}

	/**
	 * Prepare the given Script for rendering by adding the needed Labels, etc.
	 *
	 * @param Script $script Script to prepare
	 * @return \FML\Script\Features\ScriptFeature|static
	 */
	public abstract function prepare(Script $script);
}
