<?php

namespace FML\Types;

/**
 * Interface for Elements supporting ScriptFeatures
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface ScriptFeatureable {

	/**
	 * Get the assigned Script Features of the Element
	 *
	 * @return \FML\Script\Features\ScriptFeature[]
	 */
	public function getScriptFeatures();
}
