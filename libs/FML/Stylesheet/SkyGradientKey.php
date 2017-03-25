<?php

namespace FML\Stylesheet;

/**
 * Class representing a Sky Gradient Key
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class SkyGradientKey
{

    /**
     * @var float $x X value
     */
    protected $x = null;

    /**
     * @var string $color Color value
     */
    protected $color = null;

    /**
     * Create a new SkyGradientKey
     *
     * @api
     * @param float  $x     X value
     * @param string $color Color value
     * @return static
     */
    public static function create($x = null, $color = null)
    {
        return new static($x, $color);
    }

    /**
     * Construct a new SkyGradientKey
     *
     * @api
     * @param float  $x     X value
     * @param string $color Color value
     */
    public function __construct($x = null, $color = null)
    {
        if ($x) {
            $this->setX($x);
        }
        if ($color) {
            $this->setColor($color);
        }
    }

    /**
     * Get the X value
     *
     * @api
     * @return float
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Set the X value
     *
     * @api
     * @param float $x X value
     * @return static
     */
    public function setX($x)
    {
        $this->x = (float)$x;
        return $this;
    }

    /**
     * Get the Color value
     *
     * @api
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set the Color value
     *
     * @api
     * @param string $color Color value
     * @return static
     */
    public function setColor($color)
    {
        $this->color = (string)$color;
        return $this;
    }

    /**
     * Render the SkyGradientKey
     *
     * @param \DOMDocument $domDocument DOMDocument for which the Sky Gradient Key should be rendered
     * @return \DOMElement
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("key");
        $domElement->setAttribute("x", $this->x);
        $domElement->setAttribute("color", $this->color);
        return $domElement;
    }

}
