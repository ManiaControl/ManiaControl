<?php

namespace FML\Script;

use FML\Types\Identifiable;

/**
 * ManiaScript Builder class
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Builder
{

    /*
     * Constants
     */
    const EMPTY_STRING = '""';

    /**
     * Build a script label implementation block
     *
     * @api
     * @param string $labelName          Name of the label
     * @param string $implementationCode Label implementation coding (without declaration)
     * @param bool   $isolate            (optional) If the code should be isolated in an own block
     * @return string
     */
    public static function getLabelImplementationBlock($labelName, $implementationCode, $isolate = true)
    {
        if ($isolate) {
            $implementationCode = "if(True){{$implementationCode}}";
        }
        return "
***{$labelName}***
***{$implementationCode}***
";
    }

    /**
     * Escape dangerous characters in the given text
     *
     * @api
     * @param string $text           Text to escape
     * @param bool   $addApostrophes (optional) Add apostrophes before and after the text
     * @return string
     */
    public static function escapeText($text, $addApostrophes = true)
    {
        $dangers      = array('\\', '"', "\n");
        $replacements = array('\\\\', '\\"', '\\n');
        $escapedText  = str_ireplace($dangers, $replacements, $text);
        if ($addApostrophes) {
            $escapedText = '"' . $escapedText . '"';
        }
        return $escapedText;
    }

    /**
     * Get the escaped Id of the given Element
     *
     * @param Identifiable $element Element
     * @return string
     */
    public static function getId(Identifiable $element)
    {
        return static::escapeText($element->getId(), false);
    }

    /**
     * Get the 'Text' string representation of the given value
     *
     * @api
     * @param string $value String value to convert to a ManiaScript 'Text'
     * @return string
     */
    public static function getText($value)
    {
        return '"' . (string)$value . '"';
    }

    /**
     * Get the 'Integer' string representation of the given value
     *
     * @api
     * @param int $value Int value to convert to a ManiaScript 'Integer'
     * @return string
     */
    public static function getInteger($value)
    {
        return (string)(int)$value;
    }

    /**
     * Get the 'Real' string representation of the given value
     *
     * @api
     * @param float $value Float value to convert to a ManiaScript 'Real'
     * @return string
     */
    public static function getReal($value)
    {
        $value     = (float)$value;
        $stringVal = (string)$value;
        if (!fmod($value, 1)) {
            $stringVal .= ".";
        }
        return $stringVal;
    }

    /**
     * Get the 'Boolean' string representation of the given value
     *
     * @api
     * @param bool $value Value to convert to a ManiaScript 'Boolean'
     * @return string
     */
    public static function getBoolean($value)
    {
        $bool = (bool)$value;
        if ($bool) {
            return "True";
        }
        return "False";
    }

    /**
     * Get the Vec3 representation for the given values
     *
     * @api
     * @param float|float[] $valueX Value X
     * @param float         $valueY (optional) Value Y
     * @return string
     */
    public static function getVec2($valueX, $valueY = null)
    {
        if (is_array($valueX)) {
            $valueY = (isset($valueX[1]) ? $valueX[1] : 0.);
            $valueX = (isset($valueX[0]) ? $valueX[0] : 0.);
        }
        return "<" . static::getReal($valueX) . "," . static::getReal($valueY) . ">";
    }

    /**
     * Get the Vec3 representation for the given values
     *
     * @api
     * @param float|float[] $valueX Value X
     * @param float         $valueY (optional) Value Y
     * @param float         $valueZ (optional) Value Z
     * @return string
     */
    public static function getVec3($valueX, $valueY = null, $valueZ = null)
    {
        if (is_array($valueX)) {
            $valueZ = (isset($valueX[2]) ? $valueX[2] : 0.);
            $valueY = (isset($valueX[1]) ? $valueX[1] : 0.);
            $valueX = (isset($valueX[0]) ? $valueX[0] : 0.);
        }
        return "<" . static::getReal($valueX) . "," . static::getReal($valueY) . "," . static::getReal($valueZ) . ">";
    }

    /**
     * Get the string representation of the given array
     *
     * @api
     * @param array $array       Array to convert to a ManiaScript array
     * @param bool  $associative (optional) Whether the array should be associative
     * @return string
     */
    public static function getArray(array $array, $associative = true)
    {
        $arrayText = "[";
        $index     = 0;
        $count     = count($array);
        foreach ($array as $key => $value) {
            if ($associative) {
                $arrayText .= static::getValue($key);
                $arrayText .= " => ";
            }
            $arrayText .= static::getValue($value);
            if ($index < $count - 1) {
                $arrayText .= ", ";
                $index++;
            }
        }
        return $arrayText . "]";
    }

    /**
     * Get the string representation for the given value
     *
     * @api
     * @param mixed $value Value
     * @return string
     */
    public static function getValue($value)
    {
        if (is_string($value)) {
            return static::escapeText($value);
        }
        if (is_bool($value)) {
            return static::getBoolean($value);
        }
        if (is_array($value)) {
            return static::getArray($value);
        }
        return $value;
    }

    /**
     * Get the include command for the given file and namespace
     *
     * @api
     * @param string $file      Include file
     * @param string $namespace (optional) Include namespace
     * @return string
     */
    public static function getInclude($file, $namespace = null)
    {
        if (!$namespace && stripos($file, ".") === false) {
            $namespace = $file;
        }
        $file        = static::escapeText($file);
        $includeText = "#Include	{$file}";
        if ($namespace) {
            $includeText .= "	as {$namespace}";
        }
        return $includeText . "
";
    }

    /**
     * Get the constant command for the given name and value
     *
     * @api
     * @param string $name  Constant name
     * @param string $value Constant value
     * @return string
     */
    public static function getConstant($name, $value)
    {
        $value = static::getValue($value);
        return "#Const	{$name}	{$value}
";
    }

}
