<?php

namespace FML\Script;

/**
 * Class representing a Function of the ManiaLink Script
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptFunction
{

    /**
     * @var string $name Function name
     */
    protected $name = null;

    /**
     * @var string $text Function text
     */
    protected $text = null;

    /**
     * Construct a new Script Function
     *
     * @api
     * @param string $name (optional) Function name
     * @param string $text (optional) Function text
     */
    public function __construct($name = null, $text = null)
    {
        if ($name) {
            $this->setName($name);
        }
        if ($text) {
            $this->setText($text);
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
     * @param string $name Function name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get the text
     *
     * @api
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the text
     *
     * @api
     * @param string $text Function text
     * @return static
     */
    public function setText($text)
    {
        $this->text = (string)$text;
        return $this;
    }

    /**
     * Get the Script Function text
     *
     * @return string
     */
    public function __toString()
    {
        return $this->text;
    }

}
