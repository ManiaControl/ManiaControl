<?php

namespace FML\Elements;

use FML\Types\Renderable;

/**
 * Include Element
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Including implements Renderable
{

    /**
     * @var string $url Include url
     */
    protected $url = null;

    /**
     * Create a new Include
     *
     * @api
     * @param string $url (optional) Include url
     * @return static
     */
    public static function create($url = null)
    {
        return new static($url);
    }

    /**
     * Construct a new Include
     *
     * @api
     * @param string $url (optional) Include url
     */
    public function __construct($url = null)
    {
        if ($url) {
            $this->setUrl($url);
        }
    }

    /**
     * Get the url
     *
     * @api
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the url
     *
     * @api
     * @param string $url Include url
     * @return static
     */
    public function setUrl($url)
    {
        $this->url = (string)$url;
        return $this;
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("include");
        if ($this->url) {
            $domElement->setAttribute("url", $this->url);
        }
        return $domElement;
    }

}
