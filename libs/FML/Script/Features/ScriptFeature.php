<?php

namespace FML\Script\Features;

use FML\Script\Script;
use FML\Types\ScriptFeatureable;

/**
 * ManiaLink Script Feature Class
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class ScriptFeature
{

    /**
     * Collect the Script Features of the given objects
     *
     * @return ScriptFeature[]
     */
    public static function collect()
    {
        $params         = func_get_args();
        $scriptFeatures = array();
        foreach ($params as $object) {
            if ($object instanceof ScriptFeature) {
                $scriptFeatures = static::addScriptFeature($scriptFeatures, $object);
            } else if ($object instanceof ScriptFeatureable) {
                $scriptFeatures = static::addScriptFeature($scriptFeatures, $object->getScriptFeatures());
            } else if (is_array($object)) {
                foreach ($object as $subObject) {
                    $scriptFeatures = static::addScriptFeature($scriptFeatures, static::collect($subObject));
                }
            }
        }
        return $scriptFeatures;
    }

    /**
     * Add one or more Script Features to an Array of Features if they are not already contained
     *
     * @param array $scriptFeatures
     * @param ScriptFeature||ScriptFeature[] $newScriptFeatures
     * @return array
     */
    public static function addScriptFeature(array $scriptFeatures, $newScriptFeatures)
    {
        if (!$newScriptFeatures) {
            return $scriptFeatures;
        }
        if ($newScriptFeatures instanceof ScriptFeature) {
            if (!in_array($newScriptFeatures, $scriptFeatures, true)) {
                array_push($scriptFeatures, $newScriptFeatures);
            }
        } else if (is_array($newScriptFeatures)) {
            foreach ($newScriptFeatures as $newScriptFeature) {
                $scriptFeatures = static::addScriptFeature($scriptFeatures, $newScriptFeature);
            }
        }
        return $scriptFeatures;
    }

    /**
     * Prepare the given Script for rendering by adding the needed Labels, etc.
     *
     * @param Script $script Script to prepare
     * @return static
     */
    public abstract function prepare(Script $script);

}
