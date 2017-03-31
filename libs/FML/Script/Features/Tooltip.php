<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Controls\Label;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for showing Tooltips
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Tooltip extends ScriptFeature
{

    /**
     * @var Control $hoverControl Hover Control
     */
    protected $hoverControl = null;

    /**
     * @var Control $tooltipControl Tooltip Control
     */
    protected $tooltipControl = null;

    /**
     * @var bool $stayOnClick Stay on click
     */
    protected $stayOnClick = null;

    /**
     * @var bool $invert Inverted visibility toggling
     */
    protected $invert = null;

    /**
     * @var string $text Tooltip Text
     */
    protected $text = null;

    /**
     * Construct a new Tooltip
     *
     * @api
     * @param Control $hoverControl   (optional) Hover Control
     * @param Control $tooltipControl (optional) Tooltip Control
     * @param bool    $stayOnClick    (optional) If the Tooltip should stay on click
     * @param bool    $invert         (optional) If the visibility toggling should be inverted
     * @param string  $text           (optional) Text to display if the TooltipControl is a Label
     */
    public function __construct(Control $hoverControl = null, Control $tooltipControl = null, $stayOnClick = null, $invert = null, $text = null)
    {
        if ($hoverControl) {
            $this->setHoverControl($hoverControl);
        }
        if ($tooltipControl) {
            $this->setTooltipControl($tooltipControl);
        }
        if ($stayOnClick) {
            $this->setStayOnClick($stayOnClick);
        }
        if ($invert) {
            $this->setInvert($invert);
        }
        if ($text) {
            $this->setText($text);
        }
    }

    /**
     * Get the Hover Control
     *
     * @api
     * @return Control
     */
    public function getHoverControl()
    {
        return $this->hoverControl;
    }

    /**
     * Set the Hover Control
     *
     * @api
     * @param Control $hoverControl Hover Control
     * @return static
     */
    public function setHoverControl(Control $hoverControl)
    {
        $hoverControl->checkId();
        if ($hoverControl instanceof Scriptable) {
            $hoverControl->setScriptEvents(true);
        }
        $this->hoverControl = $hoverControl;
        return $this;
    }

    /**
     * Get the Tooltip Control
     *
     * @api
     * @return Control
     */
    public function getTooltipControl()
    {
        return $this->tooltipControl;
    }

    /**
     * Set the Tooltip Control
     *
     * @api
     * @param Control $tooltipControl Tooltip Control
     * @return static
     */
    public function setTooltipControl(Control $tooltipControl)
    {
        $tooltipControl->checkId();
        $tooltipControl->setVisible(false);
        $this->tooltipControl = $tooltipControl;
        return $this;
    }

    /**
     * Get the staying on click
     *
     * @api
     * @return bool
     */
    public function getStayOnClick()
    {
        return $this->stayOnClick;
    }

    /**
     * Set the staying on click
     *
     * @api
     * @param bool $stayOnClick If the Tooltip should stay on click
     * @return static
     */
    public function setStayOnClick($stayOnClick)
    {
        $this->stayOnClick = (bool)$stayOnClick;
        return $this;
    }

    /**
     * Get inverting of the visibility
     *
     * @api
     * @return bool
     */
    public function getInvert()
    {
        return $this->invert;
    }

    /**
     * Set inverting of the visibility
     *
     * @api
     * @param bool $invert If the visibility toggling should be inverted
     * @return static
     */
    public function setInvert($invert)
    {
        $this->invert = (bool)$invert;
        return $this;
    }

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
     * @param string $text Text to display if the TooltipControl is a Label
     * @return static
     */
    public function setText($text)
    {
        $this->text = (string)$text;
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        $hoverControlId   = Builder::escapeText($this->hoverControl->getId());
        $tooltipControlId = Builder::escapeText($this->tooltipControl->getId());

        // MouseOver
        $visibility = Builder::getBoolean(!$this->invert);
        $scriptText = "
if (Event.Control.ControlId == {$hoverControlId}) {
	declare TooltipControl = Page.GetFirstChild({$tooltipControlId});
	TooltipControl.Visible = {$visibility};";
        if (is_string($this->text) && ($this->tooltipControl instanceof Label)) {
            $tooltipText = Builder::escapeText($this->text);
            $scriptText .= "
	declare TooltipLabel = (TooltipControl as CMlLabel);
	TooltipLabel.Value = {$tooltipText};";
        }
        $scriptText .= "
}";
        $script->appendGenericScriptLabel(ScriptLabel::MOUSEOVER, $scriptText);

        // MouseOut
        $visibility = Builder::getBoolean($this->invert);
        $scriptText = "
if (Event.Control.ControlId == {$hoverControlId}) {
	declare TooltipControl = Page.GetFirstChild({$tooltipControlId});";
        if ($this->stayOnClick) {
            $scriptText .= "
	declare FML_Clicked for Event.Control = False;
	if (!FML_Clicked) ";
        }
        $scriptText .= "
	TooltipControl.Visible = {$visibility};
}";
        $script->appendGenericScriptLabel(ScriptLabel::MOUSEOUT, $scriptText);

        // MouseClick
        if ($this->stayOnClick) {
            $scriptText = "
if (Event.Control.ControlId == {$hoverControlId}) {
	declare FML_Clicked for Event.Control = False;
	FML_Clicked = !FML_Clicked;
}";
            $script->appendGenericScriptLabel(ScriptLabel::MOUSECLICK, $scriptText);
        }
        return $this;
    }

}
