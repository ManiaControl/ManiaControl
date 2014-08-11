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
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Tooltip extends ScriptFeature {
	/*
	 * Protected properties
	 */
	/** @var Control $hoverControl */
	protected $hoverControl = null;
	/** @var Control $tooltipControl */
	protected $tooltipControl = null;
	protected $stayOnClick = null;
	protected $invert = null;
	protected $text = null;

	/**
	 * Construct a new Tooltip Feature
	 *
	 * @param Control $hoverControl   (optional) Hover Control
	 * @param Control $tooltipControl (optional) Tooltip Control
	 * @param bool    $stayOnClick    (optional) Whether the Tooltip should stay on click
	 * @param bool    $invert         (optional) Whether the visibility toggling should be inverted
	 * @param string  $text           (optional) Text to display if the TooltipControl is a Label
	 */
	public function __construct(Control $hoverControl = null, Control $tooltipControl = null, $stayOnClick = false, $invert = false, $text = null) {
		if ($hoverControl !== null) {
			$this->setHoverControl($hoverControl);
		}
		if ($tooltipControl !== null) {
			$this->setTooltipControl($tooltipControl);
		}
		$this->setStayOnClick($stayOnClick);
		$this->setInvert($invert);
		if ($text !== null) {
			$this->setText($text);
		}
	}

	/**
	 * Set the Hover Control
	 *
	 * @param Control $hoverControl Hover Control
	 * @return static
	 */
	public function setHoverControl(Control $hoverControl) {
		$hoverControl->checkId();
		if ($hoverControl instanceof Scriptable) {
			$hoverControl->setScriptEvents(true);
		}
		$this->hoverControl = $hoverControl;
		return $this;
	}

	/**
	 * Set the Tooltip Control
	 *
	 * @param Control $tooltipControl Tooltip Control
	 * @return static
	 */
	public function setTooltipControl(Control $tooltipControl) {
		$this->tooltipControl = $tooltipControl->checkId()->setVisible(false);
		return $this;
	}

	/**
	 * Set to only show
	 *
	 * @param bool $stayOnClick (optional) Whether the Tooltip should stay on click
	 * @return static
	 */
	public function setStayOnClick($stayOnClick) {
		$this->stayOnClick = (bool)$stayOnClick;
		return $this;
	}

	/**
	 * Set to only hide
	 *
	 * @param bool $invert (optional) Whether the visibility toggling should be inverted
	 * @return static
	 */
	public function setInvert($invert) {
		$this->invert = (bool)$invert;
		return $this;
	}

	/**
	 * Set text
	 *
	 * @param string $text (optional) Text to display if the TooltipControl is a Label
	 * @return static
	 */
	public function setText($text) {
		$this->text = (string)$text;
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$hoverControlId   = $this->hoverControl->getId(true, true);
		$tooltipControlId = $this->tooltipControl->getId(true, true);

		// MouseOver
		$visibility = ($this->invert ? 'False' : 'True');
		$scriptText = "
if (Event.Control.ControlId == {$hoverControlId}) {
	declare TooltipControl = Page.GetFirstChild({$tooltipControlId});
	TooltipControl.Visible = {$visibility};";
		if (is_string($this->text) && ($this->tooltipControl instanceof Label)) {
			$tooltipText = Builder::escapeText($this->text, true);
			$scriptText .= "
	declare TooltipLabel = (TooltipControl as CMlLabel);
	TooltipLabel.Value = {$tooltipText};";
		}
		$scriptText .= "
}";
		$script->appendGenericScriptLabel(ScriptLabel::MOUSEOVER, $scriptText);

		// MouseOut
		$visibility = ($this->invert ? 'True' : 'False');
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
