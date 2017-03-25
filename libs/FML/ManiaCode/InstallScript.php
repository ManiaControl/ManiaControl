<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a script
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class InstallScript implements Element
{

    /**
     * @var string $name Script name
     */
    protected $name = null;

    /**
     * @var string $file Script file
     */
    protected $file = null;

    /**
     * @var string $url Script url
     */
    protected $url = null;

    /**
     * Create a new InstallScript Element
     *
     * @api
     * @param string $name (optional) Script name
     * @param string $file (optional) Script file
     * @param string $url  (optional) Script url
     * @return static
     */
    public static function create($name = null, $file = null, $url = null)
    {
        return new static($name, $file, $url);
    }

    /**
     * Construct a new InstallScript Element
     *
     * @api
     * @param string $name (optional) Script name
     * @param string $file (optional) Script file
     * @param string $url  (optional) Script url
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
     * Get the script name
     *
     * @api
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the script name
     *
     * @api
     * @param string $name Script name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get the script file
     *
     * @api
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set the script file
     *
     * @api
     * @param string $file Script file
     * @return static
     */
    public function setFile($file)
    {
        $this->file = (string)$file;
        return $this;
    }

    /**
     * Get the script url
     *
     * @api
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the script url
     *
     * @api
     * @param string $url Script url
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
        $domElement = $domDocument->createElement("install_script");

        $nameElement = $domDocument->createElement("name", $this->name);
        $domElement->appendChild($nameElement);

        $fileElement = $domDocument->createElement("file", $this->file);
        $domElement->appendChild($fileElement);

        $urlElement = $domDocument->createElement("url", $this->url);
        $domElement->appendChild($urlElement);

        return $domElement;
    }

}
