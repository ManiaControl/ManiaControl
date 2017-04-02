<?php

namespace FML\Controls;

use FML\Types\Colorable;
use FML\Types\Styleable;

/**
 * Gauge Control
 * (CMlGauge)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Gauge extends Control implements Colorable, Styleable
{

    /*
     * Constants
     */
    const STYLE_BgCard           = "BgCard";
    const STYLE_EnergyBar        = "EnergyBar";
    const STYLE_ProgressBar      = "ProgressBar";
    const STYLE_ProgressBarSmall = "ProgressBarSmall";

    /**
     * @var float $ratio Ratio
     */
    protected $ratio = 0.0;

    /**
     * @var float $grading Grading
     */
    protected $grading = 1.;

    /**
     * @var string $color Color
     */
    protected $color = null;

    /**
     * @var bool $centered Centered
     */
    protected $centered = null;

    /**
     * @var int $clan Clan number
     */
    protected $clan = null;

    /**
     * @var bool $drawBackground Draw background
     */
    protected $drawBackground = true;

    /**
     * @var bool $drawBlockBackground Draw block background
     */
    protected $drawBlockBackground = true;

    /**
     * @var string $style Style
     */
    protected $style = null;

    /**
     * Get the ratio
     *
     * @api
     * @return float
     */
    public function getRatio()
    {
        return $this->ratio;
    }

    /**
     * Set the ratio
     *
     * @api
     * @param float $ratio Ratio value
     * @return static
     */
    public function setRatio($ratio)
    {
        $this->ratio = (float)$ratio;
        return $this;
    }

    /**
     * Get the grading
     *
     * @api
     * @return float
     */
    public function getGrading()
    {
        return $this->grading;
    }

    /**
     * Set the grading
     *
     * @api
     * @param float $grading Grading value
     * @return static
     */
    public function setGrading($grading)
    {
        $this->grading = (float)$grading;
        return $this;
    }

    /**
     * @see Colorable::getColor
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @see Colorable::setColor
     */
    public function setColor($color)
    {
        $this->color = (string)$color;
        return $this;
    }

    /**
     * Get centered
     *
     * @api
     * @return bool
     */
    public function getCentered()
    {
        return $this->centered;
    }

    /**
     * Set centered
     *
     * @api
     * @param bool $centered If the Gauge should be centered
     * @return static
     */
    public function setCentered($centered)
    {
        $this->centered = (bool)$centered;
        return $this;
    }

    /**
     * Get the clan
     *
     * @api
     * @return int
     */
    public function getClan()
    {
        return $this->clan;
    }

    /**
     * Set the clan
     *
     * @api
     * @param int $clan Clan number
     * @return static
     */
    public function setClan($clan)
    {
        $this->clan = (int)$clan;
        return $this;
    }

    /**
     * Get draw background
     *
     * @api
     * @return bool
     */
    public function getDrawBackground()
    {
        return $this->drawBackground;
    }

    /**
     * Set draw background
     *
     * @api
     * @param bool $drawBackground If the Gauge background should be drawn
     * @return static
     * @deprecated Use setDrawBackground()
     * @see        Gauge::setDrawBackground()
     */
    public function setDrawBg($drawBackground)
    {
        return $this->setDrawBackground($drawBackground);
    }

    /**
     * Set draw background
     *
     * @api
     * @param bool $drawBackground If the Gauge background should be drawn
     * @return static
     */
    public function setDrawBackground($drawBackground)
    {
        $this->drawBackground = (bool)$drawBackground;
        return $this;
    }

    /**
     * Get draw block background
     *
     * @api
     * @return bool
     */
    public function getDrawBlockBackground()
    {
        return $this->drawBlockBackground;
    }

    /**
     * Set draw block background
     *
     * @api
     * @param bool $drawBlockBackground If the Gauge block background should be drawn
     * @return static
     */
    public function setDrawBlockBackground($drawBlockBackground)
    {
        $this->drawBlockBackground = (bool)$drawBlockBackground;
        return $this;
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
        return $this;
    }

    /**
     * @see Control::getTagName()
     */
    public function getTagName()
    {
        return "gauge";
    }

    /**
     * @see Control::getManiaScriptClass()
     */
    public function getManiaScriptClass()
    {
        return "CMlGauge";
    }

    /**
     * @see Control::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = parent::render($domDocument);
        if ($this->ratio) {
            $domElement->setAttribute("ratio", $this->ratio);
        }
        if ($this->grading !== 1.) {
            $domElement->setAttribute("grading", $this->grading);
        }
        if ($this->color) {
            $domElement->setAttribute("color", $this->color);
        }
        if ($this->centered) {
            $domElement->setAttribute("centered", 1);
        }
        if ($this->clan) {
            $domElement->setAttribute("clan", $this->clan);
        }
        if (!$this->drawBackground) {
            $domElement->setAttribute("drawbg", 0);
        }
        if (!$this->drawBlockBackground) {
            $domElement->setAttribute("drawblockbg", 0);
        }
        if ($this->style) {
            $domElement->setAttribute("style", $this->style);
        }
        return $domElement;
    }

}
