<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a replay
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class InstallReplay implements Element
{

    /**
     * @var string $name Replay name
     */
    protected $name = null;

    /**
     * @var string $url Replay url
     */
    protected $url = null;

    /**
     * Create a new InstallReplay Element
     *
     * @api
     * @param string $name (optional) Replay name
     * @param string $url  (optional) Replay url
     * @return static
     */
    public static function create($name = null, $url = null)
    {
        return new static($name, $url);
    }

    /**
     * Construct a new InstallReplay Element
     *
     * @api
     * @param string $name (optional) Replay name
     * @param string $url  (optional) Replay url
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
     * Get the replay name
     *
     * @api
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the replay name
     *
     * @api
     * @param string $name Replay name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get the replay url
     *
     * @api
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the replay url
     *
     * @api
     * @param string $url Replay url
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
        $domElement = $domDocument->createElement("install_replay");

        $nameElement = $domDocument->createElement("name", $this->name);
        $domElement->appendChild($nameElement);

        $urlElement = $domDocument->createElement("url", $this->url);
        $domElement->appendChild($urlElement);

        return $domElement;
    }

}
