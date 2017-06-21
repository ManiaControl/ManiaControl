<?php

namespace FML\Controls;

use FML\Components\CheckBoxDesign;
use FML\Types\Actionable;
use FML\Types\BackgroundColorable;
use FML\Types\BgColorable;
use FML\Types\Imageable;
use FML\Types\Linkable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\SubStyleable;

/**
 * Quad Control
 * (CMlQuad)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad extends Control implements Actionable, BackgroundColorable, BgColorable, Imageable, Linkable, Scriptable, Styleable, SubStyleable
{

    /*
     * Constants
     */
    const KEEP_RATIO_INACTIVE = "inactive";
    const KEEP_RATIO_CLIP     = "Clip";
    const KEEP_RATIO_FIT      = "Fit";

    /**
     * @var string $imageUrl Image url
     */
    protected $imageUrl = null;

    /**
     * @var string $imageId Image ID
     */
    protected $imageId = null;

    /**
     * @var string $imageFocusUrl Focus image url
     */
    protected $imageFocusUrl = null;

    /**
     * @var string $imageFocusId Focus image ID
     */
    protected $imageFocusId = null;

    /**
     * @var string $colorize Colorize value
     */
    protected $colorize = null;

    /**
     * @var string $modulizeColor Modulization color
     */
    protected $modulizeColor = null;

    /**
     * @var bool $autoScale Automatic scaling
     */
    protected $autoScale = true;

    /**
     * @var float $autoScaleFixedWidth Fixed width for automatic scaling
     */
    protected $autoScaleFixedWidth = -1.;

    /**
     * @var string $keepRatio Keep ratio mode
     */
    protected $keepRatio = null;

    /**
     * @var float $opacity Opacity
     */
    protected $opacity = 1.;

    /**
     * @var string $backgroundColor Background color
     */
    protected $backgroundColor = null;

    /**
     * @var string $focusBackgroundColor Focus background color
     */
    protected $focusBackgroundColor = null;

    /**
     * @var string $action Action name
     */
    protected $action = null;

    /**
     * @var int $actionKey Action key
     */
    protected $actionKey = null;

    /**
     * @var string $url Link url
     */
    protected $url = null;

    /**
     * @var string $urlId Link url ID
     */
    protected $urlId = null;

    /**
     * @var string $manialink Manialink
     */
    protected $manialink = null;

    /**
     * @var string $manialinkId Manialink ID
     */
    protected $manialinkId = null;

    /**
     * @var bool $scriptEvents Script events usage
     */
    protected $scriptEvents = null;

    /**
     * @var string $scriptAction Script action
     */
    protected $scriptAction = null;

    /**
     * @var string[] $scriptActionParameters Script action parameters
     */
    protected $scriptActionParameters = null;

    /**
     * @var string $style Style
     */
    protected $style = null;

    /**
     * @var string $subStyle SubStyle
     */
    protected $subStyle = null;

    /**
     * @var bool $styleSelected Style selected
     */
    protected $styleSelected = null;

    /**
     * @see Imageable::getImageUrl()
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * @deprecated Use setImageUrl()
     * @see        Quad::setImageUrl()
     */
    public function setImage($imageUrl)
    {
        return $this->setImageUrl($imageUrl);
    }

    /**
     * @see Imageable::setImageUrl()
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = (string)$imageUrl;
        return $this;
    }

    /**
     * Get the image id to use from Dico
     *
     * @api
     * @return string
     */
    public function getImageId()
    {
        return $this->imageId;
    }

    /**
     * Set the image id to use from Dico
     *
     * @api
     * @param string $imageId Image id
     * @return static
     */
    public function setImageId($imageId)
    {
        $this->imageId = (string)$imageId;
        return $this;
    }

    /**
     * Get the focus image url
     *
     * @api
     * @return string
     */
    public function getImageFocusUrl()
    {
        return $this->imageFocusUrl;
    }

    /**
     * Set the focus image url
     *
     * @api
     * @param string $imageFocusUrl Focus image url
     * @return static
     * @deprecated Use setImageFocusUrl()
     * @see        Quad::setImageFocusUrl()
     */
    public function setImageFocus($imageFocusUrl)
    {
        return $this->setImageFocusUrl($imageFocusUrl);
    }

    /**
     * Set the focus image url
     *
     * @api
     * @param string $imageFocusUrl Focus image url
     * @return static
     */
    public function setImageFocusUrl($imageFocusUrl)
    {
        $this->imageFocusUrl = (string)$imageFocusUrl;
        return $this;
    }

    /**
     * Get the focus image id to use from Dico
     *
     * @api
     * @return string
     */
    public function getImageFocusId()
    {
        return $this->imageFocusId;
    }

    /**
     * Set the focus image id to use from Dico
     *
     * @api
     * @param string $imageFocusId Focus image id
     * @return static
     */
    public function setImageFocusId($imageFocusId)
    {
        $this->imageFocusId = (string)$imageFocusId;
        return $this;
    }

    /**
     * Get the colorization
     *
     * @api
     * @return string
     */
    public function getColorize()
    {
        return $this->colorize;
    }

    /**
     * Set the colorization
     *
     * @api
     * @param string $colorize Colorize value
     * @return static
     */
    public function setColorize($colorize)
    {
        $this->colorize = (string)$colorize;
        return $this;
    }

    /**
     * Get the modulization color
     *
     * @api
     * @return string
     */
    public function getModulizeColor()
    {
        return $this->modulizeColor;
    }

    /**
     * Set the modulization color
     *
     * @api
     * @param string $modulizeColor Modulization color
     * @return static
     */
    public function setModulizeColor($modulizeColor)
    {
        $this->modulizeColor = (string)$modulizeColor;
        return $this;
    }

    /**
     * Get the automatic image scaling
     *
     * @api
     * @return bool
     */
    public function getAutoScale()
    {
        return $this->autoScale;
    }

    /**
     * Set the automatic image scaling
     *
     * @api
     * @param bool $autoScale If the image should scale automatically
     * @return static
     */
    public function setAutoScale($autoScale)
    {
        $this->autoScale = (bool)$autoScale;
        return $this;
    }

    /**
     * Get the fixed width for automatic image scaling
     *
     * @api
     * @return float
     */
    public function getAutoScaleFixedWidth()
    {
        return $this->autoScaleFixedWidth;
    }

    /**
     * Set the fixed width for automatic image scaling
     *
     * @api
     * @param float $autoScaleFixedWidth Fixed width for automatic image scaling
     * @return static
     */
    public function setAutoScaleFixedWidth($autoScaleFixedWidth)
    {
        $this->autoScaleFixedWidth = (float)$autoScaleFixedWidth;
        return $this;
    }

    /**
     * Get the Keep Ratio mode
     *
     * @api
     * @return string
     */
    public function getKeepRatio()
    {
        return $this->keepRatio;
    }

    /**
     * Set the Keep Ratio mode
     *
     * @api
     * @param string $keepRatio Keep Ratio mode
     * @return static
     */
    public function setKeepRatio($keepRatio)
    {
        $this->keepRatio = (string)$keepRatio;
        return $this;
    }

    /**
     * Get the opacity
     *
     * @api
     * @return float
     */
    public function getOpacity()
    {
        return $this->opacity;
    }

    /**
     * Set the opacity
     *
     * @api
     * @param float $opacity Opacity value
     * @return static
     */
    public function setOpacity($opacity)
    {
        $this->opacity = (float)$opacity;
        return $this;
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
     * @see        Quad::setBackgroundColor()
     */
    public function setBgColor($bgColor)
    {
        return $this->setBackgroundColor($bgColor);
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
     * @see Actionable::getAction()
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @see Actionable::setAction()
     */
    public function setAction($action)
    {
        $this->action = (string)$action;
        return $this;
    }

    /**
     * @see Actionable::getActionKey()
     */
    public function getActionKey()
    {
        return $this->actionKey;
    }

    /**
     * @see Actionable::setActionKey()
     */
    public function setActionKey($actionKey)
    {
        $this->actionKey = (int)$actionKey;
        return $this;
    }

    /**
     * @see Linkable::getUrl()
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @see Linkable::setUrl()
     */
    public function setUrl($url)
    {
        $this->url = (string)$url;
        return $this;
    }

    /**
     * @see Linkable::getUrlId()
     */
    public function getUrlId()
    {
        return $this->urlId;
    }

    /**
     * @see Linkable::setUrlId()
     */
    public function setUrlId($urlId)
    {
        $this->urlId = (string)$urlId;
        return $this;
    }

    /**
     * @see Linkable::getManialink()
     */
    public function getManialink()
    {
        return $this->manialink;
    }

    /**
     * @see Linkable::setManialink()
     */
    public function setManialink($manialink)
    {
        $this->manialink = (string)$manialink;
        return $this;
    }

    /**
     * @see Linkable::getManialinkId()
     */
    public function getManialinkId()
    {
        return $this->manialinkId;
    }

    /**
     * @see Linkable::setManialinkId()
     */
    public function setManialinkId($manialinkId)
    {
        $this->manialinkId = (string)$manialinkId;
        return $this;
    }

    /**
     * @see Scriptable::getScriptEvents()
     */
    public function getScriptEvents()
    {
        return $this->scriptEvents;
    }

    /**
     * @see Scriptable::setScriptEvents()
     */
    public function setScriptEvents($scriptEvents)
    {
        $this->scriptEvents = (bool)$scriptEvents;
        return $this;
    }

    /**
     * @see Scriptable::getScriptAction()
     */
    public function getScriptAction()
    {
        return $this->scriptAction;
    }

    /**
     * @see Scriptable::setScriptAction()
     */
    public function setScriptAction($scriptAction, array $scriptActionParameters = null)
    {
        $this->scriptAction = (string)$scriptAction;
        $this->setScriptActionParameters($scriptActionParameters);
        return $this;
    }

    /**
     * @see Scriptable::getScriptActionParameters()
     */
    public function getScriptActionParameters()
    {
        return $this->scriptActionParameters;
    }

    /**
     * @see Scriptable::setScriptActionParameters()
     */
    public function setScriptActionParameters(array $scriptActionParameters = null)
    {
        $this->scriptActionParameters = $scriptActionParameters;
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
     * Get selected style
     *
     * @api
     * @return bool
     */
    public function getStyleSelected()
    {
        return $this->styleSelected;
    }

    /**
     * Set selected style
     *
     * @api
     * @param bool $styleSelected If the quad should be styled selected
     * @return static
     */
    public function setStyleSelected($styleSelected)
    {
        $this->styleSelected = (bool)$styleSelected;
        return $this;
    }

    /**
     * Apply the CheckBox Design
     *
     * @api
     * @param CheckBoxDesign $checkBoxDesign CheckBox Design
     * @return static
     * @deprecated Use CheckBoxDesign::applyToQuad()
     * @see        CheckBoxDesign::applyToQuad()
     */
    public function applyCheckBoxDesign(CheckBoxDesign $checkBoxDesign)
    {
        $checkBoxDesign->applyToQuad($this);
        return $this;
    }

    /**
     * @see Control::getTagName()
     */
    public function getTagName()
    {
        return "quad";
    }

    /**
     * @see Control::getManiaScriptClass()
     */
    public function getManiaScriptClass()
    {
        return "CMlQuad";
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = parent::render($domDocument);
        if ($this->imageUrl) {
            $domElement->setAttribute("image", $this->imageUrl);
        }
        if ($this->imageId) {
            $domElement->setAttribute("imageid", $this->imageId);
        }
        if ($this->imageFocusUrl) {
            $domElement->setAttribute("imagefocus", $this->imageFocusUrl);
        }
        if ($this->imageFocusId) {
            $domElement->setAttribute("imagefocusid", $this->imageFocusId);
        }
        if ($this->colorize) {
            $domElement->setAttribute("colorize", $this->colorize);
        }
        if ($this->modulizeColor) {
            $domElement->setAttribute("modulizecolor", $this->modulizeColor);
        }
        if (!$this->autoScale) {
            $domElement->setAttribute("autoscale", 0);
        }
        if ($this->autoScaleFixedWidth > 0.) {
            $domElement->setAttribute("autoscalefixedWidth", $this->autoScaleFixedWidth);
        }
        if ($this->keepRatio) {
            $domElement->setAttribute("keepratio", $this->keepRatio);
        }
        if ($this->opacity !== 1.) {
            $domElement->setAttribute("opacity", $this->opacity);
        }
        if ($this->backgroundColor) {
            $domElement->setAttribute("bgcolor", $this->backgroundColor);
        }
        if ($this->focusBackgroundColor) {
            $domElement->setAttribute("bgcolorfocus", $this->focusBackgroundColor);
        }
        if ($this->action) {
            $domElement->setAttribute("action", $this->action);
        }
        if ($this->actionKey) {
            $domElement->setAttribute("actionkey", $this->actionKey);
        }
        if ($this->url) {
            $domElement->setAttribute("url", $this->url);
        }
        if ($this->urlId) {
            $domElement->setAttribute("urlid", $this->urlId);
        }
        if ($this->manialink) {
            $domElement->setAttribute("manialink", $this->manialink);
        }
        if ($this->manialinkId) {
            $domElement->setAttribute("manialinkid", $this->manialinkId);
        }
        if ($this->scriptEvents) {
            $domElement->setAttribute("scriptevents", 1);
        }
        if ($this->scriptAction) {
            $scriptAction = array($this->scriptAction);
            if ($this->scriptActionParameters) {
                $scriptAction = array_merge($scriptAction, $this->scriptActionParameters);
            }
            $domElement->setAttribute("scriptaction", implode("'", $scriptAction));
        }
        if ($this->style) {
            $domElement->setAttribute("style", $this->style);
        }
        if ($this->subStyle) {
            $domElement->setAttribute("substyle", $this->subStyle);
        }
        if ($this->styleSelected) {
            $domElement->setAttribute("styleselected", 1);
        }
        return $domElement;
    }

}
