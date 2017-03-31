<?php

namespace FML\Script;

/**
 * Class representing an Include of the ManiaLink Script
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptInclude
{

    /*
     * Constants
     */
    const MATHLIB = 'MathLib';
    const TEXTLIB = 'TextLib';

    /**
     * @var string $file File name
     */
    protected $file = null;

    /**
     * @var string $namespace Namespace
     */
    protected $namespace = null;

    /**
     * Construct a new Script Include
     *
     * @api
     * @param string $file      (optional) File name
     * @param string $namespace (optional) Namespace
     */
    public function __construct($file = null, $namespace = null)
    {
        if ($file) {
            $this->setFile($file);
        }
        if ($namespace) {
            $this->setNamespace($namespace);
        }
    }

    /**
     * Get the file
     *
     * @api
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set the file
     *
     * @api
     * @param string $file File name
     * @return static
     */
    public function setFile($file)
    {
        $this->file = (string)$file;
        return $this;
    }

    /**
     * Get the namespace
     *
     * @api
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Set the namespace
     *
     * @api
     * @param string $namespace Namespace
     * @return static
     */
    public function setNamespace($namespace)
    {
        $this->namespace = (string)$namespace;
        return $this;
    }

    /**
     * Build the Script Include text
     *
     * @return string
     */
    public function __toString()
    {
        return Builder::getInclude($this->file, $this->namespace);
    }

}
