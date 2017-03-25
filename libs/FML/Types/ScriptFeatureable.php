<?php

namespace FML\Types;

use FML\Script\Features\ScriptFeature;

/**
 * Interface for Elements supporting ScriptFeatures
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface ScriptFeatureable
{

    /**
     * Get the Script Features
     *
     * @return ScriptFeature[]
     */
    public function getScriptFeatures();

}
