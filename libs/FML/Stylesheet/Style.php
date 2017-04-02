<?php

namespace FML\Stylesheet;

use FML\Controls\Control;
use FML\Types\BackgroundColorable;
use FML\Types\BgColorable;
use FML\Types\Colorable;
use FML\Types\Renderable;
use FML\Types\Styleable;
use FML\Types\SubStyleable;
use FML\Types\TextFormatable;

/**
 * Class representing a Style
 * (Note: This class doesn't support all style attributes yet. This will be improved in FML v2.)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Style implements BackgroundColorable, BgColorable, Colorable, Renderable, Styleable, SubStyleable, TextFormatable
{

    /**
     * @var string[] $styleIds Style Ids
     */
    protected $styleIds = array();

    /**
     * @var string[] $styleClasses Style classes
     */
    protected $styleClasses = array();

    /**
     * @var string $backgroundColor Background color
     */
    protected $backgroundColor = null;

    /**
     * @var string $focusBackgroundColor Focus background color
     */
    protected $focusBackgroundColor = null;

    /**
     * @var string $color Color
     */
    protected $color = null;

    /**
     * @var string $style Style
     */
    protected $style = null;

    /**
     * @var string $subStyle SubStyle
     */
    protected $subStyle = null;

    /**
     * @var int $textSize Text size
     */
    protected $textSize = null;

    /**
     * @var string $textFont Text font
     */
    protected $textFont = null;

    /**
     * @var string $textColor Text color
     */
    protected $textColor = null;

    /**
     * @var string $areaColor Area color
     */
    protected $areaColor = null;

    /**
     * @var string $focusAreaColor Focus area color
     */
    protected $focusAreaColor = null;

    /**
     * Create a new Style
     *
     * @api
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Get style Ids
     *
     * @api
     * @return string[]
     */
    public function getStyleIds()
    {
        return $this->styleIds;
    }

    /**
     * Add style Id
     *
     * @api
     * @param string $styleId Style Id
     * @return static
     */
    public function addStyleId($styleId)
    {
        $styleId = (string)$styleId;
        if (!in_array($styleId, $this->styleIds)) {
            array_push($this->styleIds, $styleId);
        }
        return $this;
    }

    /**
     * Add style Ids
     *
     * @api
     * @param string[] $styleIds Style Ids
     * @return static
     */
    public function addStyleIds(array $styleIds)
    {
        foreach ($styleIds as $styleId) {
            $this->addStyleId($styleId);
        }
        return $this;
    }

    /**
     * Set style Ids
     *
     * @api
     * @param string[] $styleIds Style Ids
     * @return static
     */
    public function setStyleIds(array $styleIds)
    {
        return $this->removeAllStyleIds()
                    ->addStyleIds($styleIds);
    }

    /**
     * Remove all style Ids
     *
     * @api
     * @return static
     */
    public function removeAllStyleIds()
    {
        $this->styleIds = array();
        return $this;
    }

    /**
     * Apply style to the given control using its Id
     *
     * @api
     * @param Control $control Control that should be styled
     * @return static
     */
    public function applyToControl(Control $control)
    {
        return $this->addStyleId($control->checkId());
    }

    /**
     * Get style classes
     *
     * @api
     * @return string[]
     */
    public function getStyleClasses()
    {
        return $this->styleClasses;
    }

    /**
     * Add style class
     *
     * @api
     * @param string $styleClass Style class
     * @return static
     */
    public function addStyleClass($styleClass)
    {
        $styleClass = (string)$styleClass;
        if (!in_array($styleClass, $this->styleClasses)) {
            array_push($this->styleClasses, $styleClass);
        }
        return $this;
    }

    /**
     * Add style classes
     *
     * @api
     * @param string[] $styleClasses Style classes
     * @return static
     */
    public function addStyleClasses(array $styleClasses)
    {
        foreach ($styleClasses as $styleClass) {
            $this->addStyleClass($styleClass);
        }
        return $this;
    }

    /**
     * Set style classes
     *
     * @api
     * @param string[] $styleIds Style classes
     * @return static
     */
    public function setStyleClasses(array $styleClasses)
    {
        return $this->removeAllStyleClasses()
                    ->addStyleClasses($styleClasses);
    }

    /**
     * Remove all style classes
     *
     * @api
     * @return static
     */
    public function removeAllStyleClasses()
    {
        $this->styleClasses = array();
        return $this;
    }

    /**
     * Apply style to the given control using its classes
     *
     * @api
     * @param Control $control Control that should be styled
     * @return static
     */
    public function applyToControlViaClasses(Control $control)
    {
        return $this->addStyleClasses($control->getClasses());
    }

    /**
     * @see BackgroundColorable::getBackgroundColor()
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * @see BackgroundColorable::setBackgroundColor()
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = (string)$backgroundColor;
        return $this;
    }

    /**
     * @deprecated Use setBackgroundColor()
     * @see        Style::setBackgroundColor()
     */
    public function setBgColor($bgdColor)
    {
        return $this->setBackgroundColor($bgdColor);
    }

    /**
     * @see BackgroundColorable::getFocusBackgroundColor()
     */
    public function getFocusBackgroundColor()
    {
        return $this->focusBackgroundColor;
    }

    /**
     * @see BackgroundColorable::setFocusBackgroundColor()
     */
    public function setFocusBackgroundColor($focusBackgroundColor)
    {
        $this->focusBackgroundColor = (string)$focusBackgroundColor;
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
     * @see Styleable::getSubStyle()
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
     * @see TextFormatable::getTextSize()
     */
    public function getTextSize()
    {
        return $this->textSize;
    }

    /**
     * @see TextFormatable::setTextSize()
     */
    public function setTextSize($textSize)
    {
        $this->textSize = (int)$textSize;
        return $this;
    }

    /**
     * @see TextFormatable::getTextFont()
     */
    public function getTextFont()
    {
        return $this->textFont;
    }

    /**
     * @see TextFormatable::setTextFont()
     */
    public function setTextFont($textFont)
    {
        $this->textFont = (string)$textFont;
        return $this;
    }

    /**
     * @see TextFormatable::getTextColor()
     */
    public function getTextColor()
    {
        return $this->textColor;
    }

    /**
     * @see TextFormatable::setTextColor()
     */
    public function setTextColor($textColor)
    {
        $this->textColor = (string)$textColor;
        return $this;
    }

    /**
     * @see TextFormatable::getAreaColor()
     */
    public function getAreaColor()
    {
        return $this->areaColor;
    }

    /**
     * @see TextFormatable::setAreaColor()
     */
    public function setAreaColor($areaColor)
    {
        $this->areaColor = (string)$areaColor;
        return $this;
    }

    /**
     * @see TextFormatable::getAreaFocusColor()
     */
    public function getAreaFocusColor()
    {
        return $this->focusAreaColor;
    }

    /**
     * @see TextFormatable::setAreaFocusColor()
     */
    public function setAreaFocusColor($areaFocusColor)
    {
        $this->focusAreaColor = (string)$areaFocusColor;
        return $this;
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("style");
        if (!empty($this->styleIds)) {
            $domElement->setAttribute("id", implode(" ", $this->styleIds));
        }
        if (!empty($this->styleClasses)) {
            $domElement->setAttribute("class", implode(" ", $this->styleClasses));
        }
        if ($this->backgroundColor) {
            $domElement->setAttribute("bgcolor", $this->backgroundColor);
        }
        if ($this->focusBackgroundColor) {
            $domElement->setAttribute("bgcolorfocus", $this->focusBackgroundColor);
        }
        if ($this->color) {
            $domElement->setAttribute("color", $this->color);
        }
        if ($this->style) {
            $domElement->setAttribute("style", $this->style);
        }
        if ($this->subStyle) {
            $domElement->setAttribute("substyle", $this->subStyle);
        }
        if ($this->textSize) {
            $domElement->setAttribute("textsize", $this->textSize);
        }
        if ($this->textFont) {
            $domElement->setAttribute("textfont", $this->textFont);
        }
        if ($this->textColor) {
            $domElement->setAttribute("textcolor", $this->textColor);
        }
        if ($this->areaColor) {
            $domElement->setAttribute("focusareacolor1", $this->areaColor);
        }
        if ($this->focusAreaColor) {
            $domElement->setAttribute("focusareacolor2", $this->focusAreaColor);
        }
        return $domElement;
    }

}
