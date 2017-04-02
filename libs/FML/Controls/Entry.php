<?php

namespace FML\Controls;

use FML\Form\Parameters;
use FML\Script\Features\EntrySubmit;
use FML\Types\NewLineable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\TextFormatable;

/**
 * Entry Control
 * (CMlEntry)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Entry extends Control implements NewLineable, Scriptable, Styleable, TextFormatable
{

    /*
     * Constants
     */
    const FORMAT_Default     = "Default";
    const FORMAT_Password    = "Password";
    const FORMAT_NewPassword = "NewPassword";

    /**
     * @var string $name Entry name
     */
    protected $name = null;

    /**
     * @var string $default Default value
     */
    protected $default = null;

    /**
     * @var bool $selectText Select text
     */
    protected $selectText = null;

    /**
     * @var bool $autoNewLine Auto new line
     * @deprecated
     */
    protected $autoNewLine = null;

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
     * @var bool $autoComplete Auto complete
     */
    protected $autoComplete = null;

    /**
     * Get the name
     *
     * @api
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name
     *
     * @api
     * @param string $name Entry name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

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
     * Get select text
     *
     * @api
     * @return bool
     */
    public function getSelectText()
    {
        return $this->selectText;
    }

    /**
     * Set select text
     *
     * @api
     * @param bool $selectText Select text
     * @return static
     */
    public function setSelectText($selectText)
    {
        $this->selectText = $selectText;
        return $this;
    }

    /**
     * @see NewLineable::getAutoNewLine()
     */
    public function getAutoNewLine()
    {
        return $this->autoNewLine;
    }

    /**
     * @see NewLineable::setAutoNewLine()
     */
    public function setAutoNewLine($autoNewLine)
    {
        $this->autoNewLine = (bool)$autoNewLine;
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
        $this->textFormat = $textFormat;
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
     * Get auto completion
     *
     * @api
     * @return bool
     */
    public function getAutoComplete()
    {
        return $this->autoComplete;
    }

    /**
     * Set auto completion
     *
     * @api
     * @param bool $autoComplete Automatically complete the default value based on the current request parameters
     * @return static
     */
    public function setAutoComplete($autoComplete)
    {
        $this->autoComplete = (bool)$autoComplete;
        return $this;
    }

    /**
     * Add a dynamic Feature submitting the Entry
     *
     * @api
     * @param string $url Submit url
     * @return static
     */
    public function addSubmitFeature($url)
    {
        $entrySubmit = new EntrySubmit($this, $url);
        return $this->addScriptFeature($entrySubmit);
    }

    /**
     * @see Control::getTagName()
     */
    public function getTagName()
    {
        return "entry";
    }

    /**
     * @see Control::getManiaScriptClass()
     */
    public function getManiaScriptClass()
    {
        return "CMlEntry";
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = parent::render($domDocument);
        if ($this->name) {
            $domElement->setAttribute("name", $this->name);
        }
        if ($this->default !== null) {
            $domElement->setAttribute("default", $this->default);
        } else if ($this->autoComplete) {
            $value = Parameters::getValue($this->name);
            if ($value) {
                $domElement->setAttribute("default", $value);
            }
        }
        if ($this->selectText) {
            $domElement->setAttribute("selecttext", 1);
        }
        if ($this->autoNewLine) {
            $domElement->setAttribute("autonewline", 1);
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
