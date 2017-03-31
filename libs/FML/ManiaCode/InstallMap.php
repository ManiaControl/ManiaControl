<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a map
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class InstallMap implements Element
{

    /**
     * @var string $name Map name
     */
    protected $name = null;

    /**
     * @var string $url Map url
     */
    protected $url = null;

    /**
     * Create a new InstallMap Element
     *
     * @api
     * @param string $name (optional) Map name
     * @param string $url  (optional) Map url
     * @return static
     */
    public static function create($name = null, $url = null)
    {
        return new static($name, $url);
    }

    /**
     * Construct a new InstallMap Element
     *
     * @api
     * @param string $name (optional) Map name
     * @param string $url  (optional) Map url
     */
    public function __construct($name = null, $url = null)
    {
        if ($name) {
            $this->setName($name);
        }
        if ($url) {
            $this->setUrl($url);
        }
    }

    /**
     * Get the map name
     *
     * @api
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the map name
     *
     * @api
     * @param string $name Map name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get the map url
     *
     * @api
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the map url
     *
     * @api
     * @param string $url Map url
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
        $domElement = $domDocument->createElement("install_map");

        $nameElement = $domDocument->createElement("name", $this->name);
        $domElement->appendChild($nameElement);

        $urlElement = $domDocument->createElement("url", $this->url);
        $domElement->appendChild($urlElement);

        return $domElement;
    }

}
