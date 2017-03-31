<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element downloading a skin
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class GetSkin implements Element
{

    /**
     * @var string $name Skin name
     */
    protected $name = null;

    /**
     * @var string $file Skin file
     */
    protected $file = null;

    /**
     * @var string $url Skin url
     */
    protected $url = null;

    /**
     * Create a new GetSkin Element
     *
     * @api
     * @param string $name (optional) Skin name
     * @param string $file (optional) Skin file
     * @param string $url  (optional) Skin url
     * @return static
     */
    public static function create($name = null, $file = null, $url = null)
    {
        return new static($name, $file, $url);
    }

    /**
     * Construct a new GetSkin Element
     *
     * @api
     * @param string $name (optional) Skin name
     * @param string $file (optional) Skin file
     * @param string $url  (optional) Skin url
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
     * Get the skin name
     *
     * @api
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the skin name
     *
     * @api
     * @param string $name Skin name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get the skin file
     *
     * @api
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set the skin file
     *
     * @api
     * @param string $file Skin file
     * @return static
     */
    public function setFile($file)
    {
        $this->file = (string)$file;
        return $this;
    }

    /**
     * Get the skin url
     *
     * @api
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the skin url
     *
     * @api
     * @param string $url Skin url
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
        $domElement = $domDocument->createElement("get_skin");

        $nameElement = $domDocument->createElement("name", $this->name);
        $domElement->appendChild($nameElement);

        $fileElement = $domDocument->createElement("file", $this->file);
        $domElement->appendChild($fileElement);

        $urlElement = $domDocument->createElement("url", $this->url);
        $domElement->appendChild($urlElement);

        return $domElement;
    }

}
