<?php

namespace FML\Stylesheet;

use FML\UniqueID;

/**
 * Class representing a Style3d
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Style3d
{

    /*
     * Constants
     */
    const MODEL_Box     = "Box";
    const MODEL_Button  = "Button";
    const MODEL_ButtonH = "ButtonH";
    const MODEL_Title   = "Title";
    const MODEL_Window  = "Window";

    /**
     * @var string $styleId Style ID
     */
    protected $styleId = null;

    /**
     * @var string $model Style model
     */
    protected $model = self::MODEL_Box;

    /**
     * @var float $thickness Thickness
     */
    protected $thickness = null;

    /**
     * @var string $color Color
     */
    protected $color = null;

    /**
     * @var string $focusColor Focus color
     */
    protected $focusColor = null;

    /**
     * @var string $lightColor Light color
     */
    protected $lightColor = null;

    /**
     * @var string $focusLightColor Focus light color
     */
    protected $focusLightColor = null;

    /**
     * @var float $yOffset Y-offset
     */
    protected $yOffset = null;

    /**
     * @var float $focusYOffset Focus Y-offset
     */
    protected $focusYOffset = null;

    /**
     * @var float $zOffset Z-offset
     */
    protected $zOffset = null;

    /**
     * @var float $focusZOffset Focus Z-offset
     */
    protected $focusZOffset = null;

    /**
     * Create a new Style3d
     *
     * @api
     * @param string $styleId (optional) Style ID
     * @param string $model   (optional) Style model
     * @return static
     */
    public static function create($styleId = null, $model = null)
    {
        return new static($styleId, $model);
    }

    /**
     * Construct a new Style3d
     *
     * @api
     * @param string $styleId (optional) Style ID
     * @param string $model   (optional) Style model
     */
    public function __construct($styleId = null, $model = null)
    {
        if ($styleId) {
            $this->setId($styleId);
        }
        if ($model) {
            $this->setModel($model);
        }
    }

    /**
     * Get the Style ID
     *
     * @api
     * @return string
     */
    public function getId()
    {
        return $this->styleId;
    }

    /**
     * Set the Style ID
     *
     * @api
     * @param string $styleId Style ID
     * @return static
     */
    public function setId($styleId)
    {
        $this->styleId = (string)$styleId;
        return $this;
    }

    /**
     * Check the ID and assign one if necessary
     *
     * @return static
     */
    public function checkId()
    {
        if (!$this->styleId) {
            $this->setId(new UniqueID());
        }
        return $this;
    }

    /**
     * Get the model
     *
     * @api
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the model
     *
     * @api
     * @param string $model Style model
     * @return static
     */
    public function setModel($model)
    {
        $this->model = (string)$model;
        return $this;
    }

    /**
     * Get the thickness
     *
     * @api
     * @return float
     */
    public function getThickness()
    {
        return $this->thickness;
    }

    /**
     * Set the thickness
     *
     * @api
     * @param float $thickness Style thickness
     * @return static
     */
    public function setThickness($thickness)
    {
        $this->thickness = (float)$thickness;
        return $this;
    }

    /**
     * Get the color
     *
     * @api
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set the color
     *
     * @api
     * @param string $color Style color
     * @return static
     */
    public function setColor($color)
    {
        $this->color = (string)$color;
        return $this;
    }

    /**
     * Get the focus color
     *
     * @api
     * @return string
     */
    public function getFocusColor()
    {
        return $this->focusColor;
    }

    /**
     * Set the focus color
     *
     * @api
     * @param string $focusColor Style focus color
     * @return static
     */
    public function setFocusColor($focusColor)
    {
        $this->focusColor = (string)$focusColor;
        return $this;
    }

    /**
     * Get the light color
     *
     * @api
     * @return string
     */
    public function getLightColor()
    {
        return $this->lightColor;
    }

    /**
     * Set the light color
     *
     * @api
     * @param string $lightColor Light color
     * @return static
     */
    public function setLightColor($lightColor)
    {
        $this->lightColor = (string)$lightColor;
        return $this;
    }

    /**
     * Get the focus light color
     *
     * @api
     * @return string
     */
    public function getFocusLightColor()
    {
        return $this->focusLightColor;
    }

    /**
     * Set the focus light color
     *
     * @api
     * @param string $focusLightColor Focus light color
     * @return static
     */
    public function setFocusLightColor($focusLightColor)
    {
        $this->focusLightColor = (string)$focusLightColor;
        return $this;
    }

    /**
     * Get the Y-offset
     *
     * @api
     * @return float
     */
    public function getYOffset()
    {
        return $this->yOffset;
    }

    /**
     * Set the Y-offset
     *
     * @api
     * @param float $yOffset Y-offset
     * @return static
     */
    public function setYOffset($yOffset)
    {
        $this->yOffset = (float)$yOffset;
        return $this;
    }

    /**
     * Get the focus Y-offset
     *
     * @api
     * @return float
     */
    public function getFocusYOffset()
    {
        return $this->focusYOffset;
    }

    /**
     * Set the focus Y-offset
     *
     * @api
     * @param float $focusYOffset Focus Y-offset
     * @return static
     */
    public function setFocusYOffset($focusYOffset)
    {
        $this->focusYOffset = (float)$focusYOffset;
        return $this;
    }

    /**
     * Get the Z-offset
     *
     * @api
     * @return float
     */
    public function getZOffset()
    {
        return $this->zOffset;
    }

    /**
     * Set the Z-offset
     *
     * @api
     * @param float $zOffset Z-offset
     * @return static
     */
    public function setZOffset($zOffset)
    {
        $this->zOffset = (float)$zOffset;
        return $this;
    }

    /**
     * Get the focus Z-offset
     *
     * @api
     * @return float
     */
    public function getFocusZOffset()
    {
        return $this->focusZOffset;
    }

    /**
     * Set the focus Z-offset
     *
     * @api
     * @param float $focusZOffset Focus Z-offset
     * @return static
     */
    public function setFocusZOffset($focusZOffset)
    {
        $this->focusZOffset = (float)$focusZOffset;
        return $this;
    }

    /**
     * Render the Style3d
     *
     * @param \DOMDocument $domDocument DOMDocument for which the Style3d should be rendered
     * @return \DOMElement
     */
    public function render(\DOMDocument $domDocument)
    {
        $style3dXml = $domDocument->createElement("style3d");
        $this->checkId();
        if ($this->styleId) {
            $style3dXml->setAttribute("id", $this->styleId);
        }
        if ($this->model) {
            $style3dXml->setAttribute("model", $this->model);
        }
        if ($this->thickness) {
            $style3dXml->setAttribute("thickness", $this->thickness);
        }
        if ($this->color) {
            $style3dXml->setAttribute("color", $this->color);
        }
        if ($this->focusColor) {
            $style3dXml->setAttribute("fcolor", $this->focusColor);
        }
        if ($this->lightColor) {
            $style3dXml->setAttribute("lightcolor", $this->lightColor);
        }
        if ($this->focusLightColor) {
            $style3dXml->setAttribute("flightcolor", $this->focusLightColor);
        }
        if ($this->yOffset) {
            $style3dXml->setAttribute("yoffset", $this->yOffset);
        }
        if ($this->focusYOffset) {
            $style3dXml->setAttribute("fyoffset", $this->focusYOffset);
        }
        if ($this->zOffset) {
            $style3dXml->setAttribute("zoffset", $this->zOffset);
        }
        if ($this->focusZOffset) {
            $style3dXml->setAttribute("fzoffset", $this->focusZOffset);
        }
        return $style3dXml;
    }

}
