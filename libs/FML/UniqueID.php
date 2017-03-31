<?php

namespace FML;

use FML\Types\Identifiable;

/**
 * Unique ID Model Class
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class UniqueID
{

    /*
     * Constants
     */
    const PREFIX = 'FML_ID_';

    /**
     * @var int $currentIndex Current global id index
     */
    protected static $currentIndex = 0;

    /**
     * @var int $index Unique id index
     */
    protected $index = null;

    /**
     * Create a new Unique ID
     *
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Check and return the Id of an Identifable Element
     *
     * @param Identifiable $element Identifable element
     * @return string
     */
    public static function check(Identifiable $element)
    {
        $elementId = $element->getId();

        if (!$elementId) {
            $element->setId(new static());
            return $element->getId();
        }

        $dangerousCharacters = array(' ', '|', PHP_EOL);
        $danger              = false;
        foreach ($dangerousCharacters as $dangerousCharacter) {
            if (stripos($elementId, $dangerousCharacter) !== false) {
                $danger = true;
                break;
            }
        }

        if ($danger) {
            trigger_error("Don't use special characters in IDs, they might cause problems! Stripping them for you...");
            $elementId = str_ireplace($dangerousCharacters, '', $elementId);
            $element->setId($elementId);
        }

        return $element->getId();
    }

    /**
     * Get a new global unique index
     *
     * @return int
     */
    protected static function newIndex()
    {
        self::$currentIndex++;
        return self::$currentIndex;
    }

    /**
     * Construct a Unique ID
     */
    public function __construct()
    {
        $this->index = static::newIndex();
    }

    /**
     * Get the Unique ID index
     *
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Get the Unique ID value
     *
     * @return string
     */
    public function getValue()
    {
        return self::PREFIX . $this->getIndex();
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }

}
