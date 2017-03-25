<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a macroblock
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class InstallMacroblock implements Element
{

    /**
     * @var string $name Macroblock name
     */
    protected $name = null;

    /**
     * @var string $file Macroblock file
     */
    protected $file = null;

    /**
     * @var string $url Macroblock url
     */
    protected $url = null;

    /**
     * Create a new InstallMacroblock Element
     *
     * @api
     * @param string $name (optional) Macroblock name
     * @param string $file (optional) Macroblock file
     * @param string $url  (optional) Macroblock url
     * @return static
     */
    public static function create($name = null, $file = null, $url = null)
    {
        return new static($name, $file, $url);
    }

    /**
     * Construct a new InstallMacroblock Element
     *
     * @api
     * @param string $name (optional) Macroblock name
     * @param string $file (optional) Macroblock file
     * @param string $url  (optional) Macroblock url
     */
    public function __construct($name = null, $file = null, $url = null)
    {
        if ($name) {
            $this->setName($name);
        }
        if ($file) {
            $this->setFile($file);
        }
        if ($url) {
            $this->setUrl($url);
        }
    }

    /**
     * Get the macroblock name
     *
     * @api
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the macroblock name
     *
     * @api
     * @param string $name Macroblock name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get the macroblock file
     *
     * @api
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set the macroblock file
     *
     * @api
     * @param string $file Macroblock file
     * @return static
     */
    public function setFile($file)
    {
        $this->file = (string)$file;
        return $this;
    }

    /**
     * Get the macroblock url
     *
     * @api
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the macroblock url
     *
     * @api
     * @param string $url Macroblock url
     * @return static
     */
    public function setUrl($url)
    {
        $this->url = (string)$url;
        return $this;
    }

    /**
     * @see Element::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("install_macroblock");

        $nameElement = $domDocument->createElement("name", $this->name);
        $domElement->appendChild($nameElement);

        $fileElement = $domDocument->createElement("file", $this->file);
        $domElement->appendChild($fileElement);

        $urlElement = $domDocument->createElement("url", $this->url);
        $domElement->appendChild($urlElement);

        return $domElement;
    }

}
