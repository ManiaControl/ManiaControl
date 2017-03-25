<?php

namespace FML\Components;

use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Types\Imageable;
use FML\Types\Styleable;
use FML\Types\SubStyleable;

/**
 * Class representing CheckBox Design
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CheckBoxDesign implements Imageable, Styleable, SubStyleable
{

    /**
     * @var string $style Style name
     */
    protected $style = null;

    /**
     * @var string $subStyle SubStyle name
     */
    protected $subStyle = null;

    /**
     * @var string $imageUrl Image url
     */
    protected $imageUrl = null;

    /**
     * Create the default Design
     *
     * @return static
     */
    public static function defaultDesign()
    {
        return new static(Quad_Icons64x64_1::STYLE, Quad_Icons64x64_1::SUBSTYLE_Check);
    }

    /**
     * Construct a new CheckBox Design
     *
     * @api
     * @param string $style    (optional) Style name or image url
     * @param string $subStyle (optional) SubStyle name
     */
    public function __construct($style = null, $subStyle = null)
    {
        if ($subStyle) {
            $this->setStyles($style, $subStyle);
        } elseif ($style) {
            $this->setImageUrl($style);
        }
    }

    /**
     * @see Styleable::getStyle()
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @see Styleable::setStyle()
     */
    public function setStyle($style)
    {
        $this->style = (string)$style;
        $this->url   = null;
        return $this;
    }

    /**
     * @see SubStyleable::getSubStyle()
     */
    public function getSubStyle()
    {
        return $this->subStyle;
    }

    /**
     * @see SubStyleable::setSubStyle()
     */
    public function setSubStyle($subStyle)
    {
        $this->subStyle = (string)$subStyle;
        $this->url      = null;
        return $this;
    }

    /**
     * @see SubStyleable::setStyles()
     */
    public function setStyles($style, $subStyle)
    {
        return $this->setStyle($style)
                    ->setSubStyle($subStyle);
    }

    /**
     * Get the image url
     *
     * @api
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * Set the image url
     *
     * @api
     * @param string $imageUrl Image url
     * @return static
     */
    public function setImageUrl($imageUrl)
    {
        $this->style    = null;
        $this->subStyle = null;
        $this->imageUrl = (string)$imageUrl;
        return $this;
    }

    /**
     * Apply the Design to the given Quad
     *
     * @api
     * @param Quad $quad CheckBox Quad
     * @return static
     */
    public function applyToQuad(Quad $quad)
    {
        if ($this->imageUrl) {
            $quad->setImageUrl($this->imageUrl);
        } elseif ($this->style) {
            $quad->setStyles($this->style, $this->subStyle);
        }
        return $this;
    }

    /**
     * Get the CheckBox Design string
     *
     * @return string
     */
    public function getDesignString()
    {
        if ($this->imageUrl) {
            return $this->imageUrl;
        }
        return $this->style . "|" . $this->subStyle;
    }

}
