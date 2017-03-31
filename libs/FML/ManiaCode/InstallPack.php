<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a title pack
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class InstallPack implements Element
{

    /**
     * @var string $name Pack name
     */
    protected $name = null;

    /**
     * @var string $file Pack file
     */
    protected $file = null;

    /**
     * @var string $url Pack url
     */
    protected $url = null;

    /**
     * Create a new InstallPack Element
     *
     * @api
     * @param string $name (optional) Pack name
     * @param string $file (optional) Pack file
     * @param string $url  (optional) Pack url
     * @return static
     */
    public static function create($name = null, $file = null, $url = null)
    {
        return new static($name, $file, $url);
    }

    /**
     * Construct a new InstallPack Element
     *
     * @api
     * @param string $name (optional) Pack name
     * @param string $file (optional) Pack file
     * @param string $url  (optional) Pack url
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
     * Get the pack name
     *
     * @api
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the pack name
     *
     * @api
     * @param string $name Pack name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get the pack file
     *
     * @api
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set the pack file
     *
     * @api
     * @param string $file Pack file
     * @return static
     */
    public function setFile($file)
    {
        $this->file = (string)$file;
        return $this;
    }

    /**
     * Get the pack url
     *
     * @api
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the pack url
     *
     * @api
     * @param string $url Pack url
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
        $domElement = $domDocument->createElement("install_pack");

        $nameElement = $domDocument->createElement("name", $this->name);
        $domElement->appendChild($nameElement);

        $fileElement = $domDocument->createElement("file", $this->file);
        $domElement->appendChild($fileElement);

        $urlElement = $domDocument->createElement("url", $this->url);
        $domElement->appendChild($urlElement);

        return $domElement;
    }

}
