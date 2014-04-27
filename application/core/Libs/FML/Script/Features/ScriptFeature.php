<?php

namespace FML\Script\Features;

use FML\Script\Script;

/**
 * ManiaLink Script Feature Class
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class ScriptFeature {

	/**
	 * Prepare the given Script for Rendering by adding the needed Labels, etc.
	 *
	 * @param Script $script Script to prepare
	 * @return \FML\Script\Features\ScriptFeature
	 */
	public abstract function prepare(Script $script);
}
