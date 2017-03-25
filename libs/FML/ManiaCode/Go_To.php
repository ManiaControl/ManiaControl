<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element for going to a link
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Go_To implements Element
{

    /**
     * @var string $link Link
     */
    protected $link = null;

    /**
     * Create a new Go_To Element
     *
     * @api
     * @param string $link (optional) Link
     * @return static
     */
    public static function create($link = null)
    {
        return new static($link);
    }

    /**
     * Construct a new Go_To Element
     *
     * @api
     * @param string $link (optional) Link
     */
    public function __construct($link = null)
    {
        if ($link) {
            $this->setLink($link);
        }
    }

    /**
     * Get the link
     *
     * @api
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set the link
     *
     * @api
     * @param string $link Link
     * @return static
     */
    public function setLink($link)
    {
        $this->link = (string)$link;
        return $this;
    }

    /**
     * @see Element::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("goto");

        $linkElement = $domDocument->createElement("link", $this->link);
        $domElement->appendChild($linkElement);

        return $domElement;
    }

}
