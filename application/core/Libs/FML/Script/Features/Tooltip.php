<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Script\Builder;


use FML\Controls\Label;

/**
 * Script Feature for Showing Tooltips
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Tooltip extends ScriptFeature {
	/*
	 * Protected Properties
	 */
	protected $hoverControl = null;
	protected $tooltipControl = null;
	protected $stayOnClick = null;
	protected $invert = null;
	protected $text = null;

	/**
	 * Construct a new Tooltip Feature
	 *
	 * @param Control $hoverControl (optional) Hover Control
	 * @param Control $tooltipControl (optional) Tooltip Control
	 * @param bool $stayOnClick (optional) Whether the Tooltip should stay on Click
	 * @param bool $invert (optional) Whether the Visibility Toggling should be inverted
	 * @param string $text (optional) The Text to display if the TooltipControl is a Label
	 */
	public function __construct(Control $hoverControl = null, Control $tooltipControl = null, $stayOnClick = false, $invert = false, $text = null) {
		$this->setHoverControl($hoverControl);
		$this->setTooltipControl($tooltipControl);
		$this->setStayOnClick($stayOnClick);
		$this->setInvert($invert);
		$this->setText($text);
	}

	/**
	 * Set the Hover Control
	 *
	 * @param Control $hoverControl Hover Control
	 * @return \FML\Script\Features\Tooltip
	 */
	public function setHoverControl(Control $hoverControl) {
		$hoverControl->checkId();
		$hoverControl->setScriptEvents(true);
		$this->hoverControl = $hoverControl;
		return $this;
	}

	/**
	 * Set the Tooltip Control
	 *
	 * @param Control $tooltipControl Tooltip Control
	 * @return \FML\Script\Features\Tooltip
	 */
	public function setTooltipControl(Control $tooltipControl) {
		$tooltipControl->checkId();
		$tooltipControl->setVisible(false);
		$this->tooltipControl = $tooltipControl;
		return $this;
	}

	/**
	 * Set only Show
	 *
	 * @param bool $stayOnClick (optional) Whether the Tooltip should stay on Click
	 * @return \FML\Script\Features\Tooltip
	 */
	public function setStayOnClick($stayOnClick) {
		$this->stayOnClick = (bool) $stayOnClick;
		return $this;
	}

	/**
	 * Set only Hide
	 *
	 * @param bool $invert (optional) Whether the Visibility Toggling should be inverted
	 * @return \FML\Script\Features\Tooltip
	 */
	public function setInvert($invert) {
		$this->invert = (bool) $invert;
		return $this;
	}

	/**
	 * Set Text
	 *
	 * @param string $text (optional) The Text to display if the TooltipControl is a Label
	 * @return \FML\Script\Features\Tooltip
	 */
	public function setText($text) {
		$this->text = $text;
		return $this;
	}

	/**
	 *
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$hoverControlId = $this->hoverControl->getId(true);
		$tooltipControlId = $this->tooltipControl->getId(true);
		
		// MouseOver
		$visibility = ($this->invert ? 'False' : 'True');
		$scriptText = "
if (Event.Control.ControlId == \"{$hoverControlId}\") {
	declare TooltipControl = Page.GetFirstChild(\"{$tooltipControlId}\");
	TooltipControl.Visible = {$visibility};";
		if (is_string($this->text) && ($this->tooltipControl instanceof Label)) {
			$tooltipText = Builder::escapeText($this->text);
			$scriptText .= "
	declare TooltipLabel = (TooltipControl as CMlLabel);
	TooltipLabel.Value = \"{$tooltipText}\";";
		}
		$scriptText .= "
}";
		$script->appendGenericScriptLabel(ScriptLabel::MOUSEOVER, $scriptText);
		
		// MouseOut
		$visibility = ($this->invert ? 'True' : 'False');
		$scriptText = "
if (Event.Control.ControlId == \"{$hoverControlId}\") {
	declare TooltipControl = Page.GetFirstChild(\"{$tooltipControlId}\");";
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
if (Event.Control.ControlId == \"{$hoverControlId}\") {
	declare FML_Clicked for Event.Control = False;
	FML_Clicked = !FML_Clicked;
}";
			$script->appendGenericScriptLabel(ScriptLabel::MOUSECLICK, $scriptText);
		}
		return $this;
	}
}
