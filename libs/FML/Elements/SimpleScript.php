<?php

namespace FML\Elements;

use FML\Types\Renderable;

/**
 * Class representing a ManiaLink script tag with a simple script text
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class SimpleScript implements Renderable
{

    /**
     * @var string $text Script text
     */
    protected $text = null;

    /**
     * Create a new SimpleScript
     *
     * @api
     * @param string $text (optional) Script text
     * @return static
     */
    public static function create($text = null)
    {
        return new static($text);
    }

    /**
     * Construct a new SimpleScript
     *
     * @api
     * @param string $text (optional) Script text
     */
    public function __construct($text = null)
    {
        if ($text) {
            $this->setText($text);
        }
    }

    /**
     * Get the script text
     *
     * @api
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the script text
     *
     * @api
     * @param string $text Complete script text
     * @return static
     */
    public function setText($text)
    {
        $this->text = (string)$text;
        return $this;
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("script");
        if ($this->text) {
            $scriptComment = $domDocument->createComment($this->text);
            $domElement->appendChild($scriptComment);
        }
        return $domElement;
    }

}
