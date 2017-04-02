<?php

namespace FML\Controls;

use FML\Types\MultiLineable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\TextFormatable;

/**
 * TextEdit Control
 * (CMlTextEdit)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class TextEdit extends Control implements MultiLineable, Scriptable, Styleable, TextFormatable
{

    /*
     * Constants
     */
    const FORMAT_Default     = "Default";
    const FORMAT_Script      = "Script";
    const FORMAT_Password    = "Password";
    const FORMAT_NewPassword = "NewPassword";

    /**
     * @var string $default Default value
     */
    protected $default = null;

    /**
     * @var bool $autoNewLine Auto new line
     */
    protected $autoNewLine = null;

    /**
     * @var float $lineSpacing Line spacing
     */
    protected $lineSpacing = 1.;

    /**
     * @var int $maxLines Maximum number of lines
     */
    protected $maxLines = -1;

    /**
     * @var bool $showLineNumbers Show lines numbers
     */
    protected $showLineNumbers = null;

    /**
     * @var string $textFormat Text format
     */
    protected $textFormat = null;

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
     * @var string $textColor Text color
     */
    protected $textColor = null;

    /**
     * @var int $textSize Text size
     */
    protected $textSize = null;

    /**
     * @var string $textFont Text font
     */
    protected $textFont = null;

    /**
     * @var string $areaColor Area color
     */
    protected $areaColor = null;

    /**
     * @var string $focusAreaColor Focus area color
     */
    protected $focusAreaColor = null;

    /**
     * Get the default value
     *
     * @api
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set the default value
     *
     * @api
     * @param string $default Default value
     * @return static
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @see MultiLineable::getAutoNewLine()
     */
    public function getAutoNewLine()
    {
        return $this->autoNewLine;
    }

    /**
     * @see MultiLineable::setAutoNewLine()
     */
    public function setAutoNewLine($autoNewLine)
    {
        $this->autoNewLine = (bool)$autoNewLine;
        return $this;
    }

    /**
     * @see MultiLineable::getLineSpacing()
     */
    public function getLineSpacing()
    {
        return $this->lineSpacing;
    }

    /**
     * @see MultiLineable::setLineSpacing()
     */
    public function setLineSpacing($lineSpacing)
    {
        $this->lineSpacing = (float)$lineSpacing;
        return $this;
    }

    /**
     * @see MultiLineable::getMaxLines()
     */
    public function getMaxLines()
    {
        return $this->maxLines;
    }

    /**
     * @see MultiLineable::setMaxLines()
     */
    public function setMaxLines($maxLines)
    {
        $this->maxLines = (int)$maxLines;
        return $this;
    }

    /**
     * Get showing of line numbers
     *
     * @api
     * @return bool
     */
    public function getShowLineNumbers()
    {
        return $this->showLineNumbers;
    }

    /**
     * Set showing of line numbers
     *
     * @api
     * @param bool $showLineNumbers Show line numbers
     * @return static
     */
    public function setShowLineNumbers($showLineNumbers)
    {
        $this->showLineNumbers = (bool)$showLineNumbers;
        return $this;
    }

    /**
     * Get text format
     *
     * @api
     * @return string
     */
    public function getTextFormat()
    {
        return $this->textFormat;
    }

    /**
     * Set text format
     *
     * @api
     * @param string $textFormat Text format
     * @return static
     */
    public function setTextFormat($textFormat)
    {
        $this->textFormat = (string)$textFormat;
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
     * @see Control::getTagName()
     */
    public function getTagName()
    {
        return "textedit";
    }

    /**
     * @see Control::getManiaScriptClass()
     */
    public function getManiaScriptClass()
    {
        return "CMlTextEdit";
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = parent::render($domDocument);
        if ($this->default !== null) {
            $domElement->setAttribute("default", $this->default);
        }
        if ($this->autoNewLine) {
            $domElement->setAttribute("autonewline", 1);
        }
        if ($this->lineSpacing !== 1.) {
            $domElement->setAttribute("linespacing", $this->lineSpacing);
        }
        if ($this->maxLines > 0) {
            $domElement->setAttribute("maxline", $this->maxLines);
        }
        if ($this->showLineNumbers) {
            $domElement->setAttribute("showlinenumbers", 1);
        }
        if ($this->textFormat) {
            $domElement->setAttribute("textformat", $this->textFormat);
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
        if ($this->textColor) {
            $domElement->setAttribute("textcolor", $this->textColor);
        }
        if ($this->textSize) {
            $domElement->setAttribute("textsize", $this->textSize);
        }
        if ($this->textFont) {
            $domElement->setAttribute("textfont", $this->textFont);
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
