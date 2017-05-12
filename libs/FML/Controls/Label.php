<?php

namespace FML\Controls;

use FML\Script\Features\Clock;
use FML\Types\Actionable;
use FML\Types\Linkable;
use FML\Types\MultiLineable;
use FML\Types\NewLineable;
use FML\Types\Scriptable;
use FML\Types\Styleable;
use FML\Types\TextFormatable;

/**
 * Label Control
 * (CMlLabel)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Label extends Control implements Actionable, Linkable, NewLineable, MultiLineable, Scriptable, Styleable, TextFormatable
{

    /**
     * @var string $text Text
     */
    protected $text = null;

    /**
     * @var string $textId Text ID
     */
    protected $textId = null;

    /**
     * @var string $textPrefix Text prefix
     */
    protected $textPrefix = null;

    /**
     * @var bool $textEmboss Text emboss
     */
    protected $textEmboss = null;

    /**
     * @var bool $translate Translate text
     */
    protected $translate = null;

    /**
     * @var int $maxLines Maximum lines
     */
    protected $maxLines = -1;

    /**
     * @var float $opacity Opacity
     */
    protected $opacity = 1.;

    /**
     * @var string $action Action
     */
    protected $action = null;

    /**
     * @var int $actionKey Action key
     */
    protected $actionKey = null;

    /**
     * @var string $url Url
     */
    protected $url = null;

    /**
     * @var string $urlId Url ID
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
     * @var bool $autoNewLine Automatic new line
     */
    protected $autoNewLine = null;

    /**
     * @var float $lineSpacing Line spacing
     */
    protected $lineSpacing = 1.;

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
     * @var int $textSize Text size
     */
    protected $textSize = -1;

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
     * Get the text
     *
     * @api
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the text
     *
     * @api
     * @param string $text Text value
     * @return static
     */
    public function setText($text)
    {
        $this->text = (string)$text;
        return $this;
    }

    /**
     * Get the text id to use from Dico
     *
     * @api
     * @return string
     */
    public function getTextId()
    {
        return $this->textId;
    }

    /**
     * Set the text id to use from Dico
     *
     * @api
     * @param string $textId Text id
     * @return static
     */
    public function setTextId($textId)
    {
        $this->textId = (string)$textId;
        return $this;
    }

    /**
     * Get the text prefix
     *
     * @api
     * @return string
     */
    public function getTextPrefix()
    {
        return $this->textPrefix;
    }

    /**
     * Set the text prefix
     *
     * @api
     * @param string $textPrefix Text prefix
     * @return static
     */
    public function setTextPrefix($textPrefix)
    {
        $this->textPrefix = (string)$textPrefix;
        return $this;
    }

    /**
     * Get text emboss
     *
     * @api
     * @return bool
     */
    public function getTextEmboss()
    {
        return $this->textEmboss;
    }

    /**
     * Set text emboss
     *
     * @api
     * @param bool $textEmboss If the text should be embossed
     * @return static
     */
    public function setTextEmboss($textEmboss)
    {
        $this->textEmboss = (bool)$textEmboss;
        return $this;
    }

    /**
     * Get translate
     *
     * @api
     * @return bool
     */
    public function getTranslate()
    {
        return $this->translate;
    }

    /**
     * Set translate
     *
     * @api
     * @param bool $translate If the text should be translated
     * @return static
     */
    public function setTranslate($translate)
    {
        $this->translate = (bool)$translate;
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
     * @param float $opacity Opacity
     * @return static
     */
    public function setOpacity($opacity)
    {
        $this->opacity = (float)$opacity;
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
     * Add a dynamic Feature showing the current time
     *
     * @api
     * @param bool $showSeconds  (optional) If the seconds should be shown
     * @param bool $showFullDate (optional) If the date should be shown
     * @return static
     */
    public function addClockFeature($showSeconds = true, $showFullDate = false)
    {
        $clock = new Clock($this, $showSeconds, $showFullDate);
        $this->addScriptFeature($clock);
        return $this;
    }

    /**
     * @see Control::getTagName()
     */
    public function getTagName()
    {
        return "label";
    }

    /**
     * @see Control::getManiaScriptClass()
     */
    public function getManiaScriptClass()
    {
        return "CMlLabel";
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = parent::render($domDocument);
        if ($this->text) {
            $domElement->setAttribute("text", $this->text);
        }
        if ($this->textId) {
            $domElement->setAttribute("textid", $this->textId);
        }
        if ($this->textPrefix) {
            $domElement->setAttribute("textprefix", $this->textPrefix);
        }
        if ($this->textEmboss) {
            $domElement->setAttribute("textemboss", $this->textEmboss);
        }
        if ($this->translate) {
            $domElement->setAttribute("translate", $this->translate);
        }
        if ($this->opacity != 1.) {
            $domElement->setAttribute("opacity", $this->opacity);
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
        if ($this->autoNewLine) {
            $domElement->setAttribute("autonewline", $this->autoNewLine);
        }
        if ($this->lineSpacing !== 1.) {
            $domElement->setAttribute("linespacing", $this->lineSpacing);
        }
        if ($this->maxLines > 0) {
            $domElement->setAttribute("maxline", $this->maxLines);
        }
        if ($this->scriptEvents) {
            $domElement->setAttribute("scriptevents", $this->scriptEvents);
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
        if ($this->textSize >= 0) {
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
