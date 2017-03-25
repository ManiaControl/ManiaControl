<?php

namespace FML\Script;

/**
 * Class representing a Constant of the ManiaLink Script
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptConstant
{

    /**
     * @var string $name Name
     */
    protected $name = null;

    /**
     * @var mixed $value Value
     */
    protected $value = null;

    /**
     * Construct a new Script Constant
     *
     * @api
     * @param string $name  (optional) Constant name
     * @param mixed  $value (optional) Constant value
     */
    public function __construct($name = null, $value = null)
    {
        if ($name) {
            $this->setName($name);
        }
        if ($value !== null) {
            $this->setValue($value);
        }
    }

    /**
     * Get the name
     *
     * @api
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name
     *
     * @api
     * @param string $name Constant name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get the value
     *
     * @api
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value
     *
     * @api
     * @param mixed $value Constant value
     * @return static
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Build the Script Constant text
     *
     * @return string
     */
    public function __toString()
    {
        return Builder::getConstant($this->name, $this->value);
    }

}
