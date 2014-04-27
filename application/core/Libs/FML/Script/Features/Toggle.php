<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Script;
use FML\Script\ScriptLabel;


/**
 * Script Feature for toggling Controls
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Toggle extends ScriptFeature {
	/*
	 * Protected Properties
	 */
	protected $togglingControl = null;
	protected $toggledControl = null;
	protected $labelName = null;
	protected $onlyShow = null;
	protected $onlyHide = null;

	/**
	 * Construct a new Toggle Feature
	 *
	 * @param Control $togglingControl (optional) Toggling Control
	 * @param Control $toggledControl (optional) Toggled Control
	 * @param string $labelName (optional) Script Label Name
	 * @param bool $onlyShow (optional) Whether it should only Show the Control but not toggle
	 * @param bool $onlyHide (optional) Whether it should only Hide the Control but not toggle
	 */
	public function __construct(Control $togglingControl = null, Control $toggledControl = null, $labelName = ScriptLabel::MOUSECLICK, $onlyShow = false, $onlyHide = false) {
		$this->setTogglingControl($togglingControl);
		$this->setToggledControl($toggledControl);
		$this->setLabelName($labelName);
		$this->setOnlyShow($onlyShow);
		$this->setOnlyHide($onlyHide);
	}

	/**
	 * Set the Toggling Control
	 *
	 * @param Control $control Toggling Control
	 * @return \FML\Script\Features\Toggle
	 */
	public function setTogglingControl(Control $control) {
		$control->checkId();
		$control->setScriptEvents(true);
		$this->togglingControl = $control;
		return $this;
	}

	/**
	 * Set the Toggled Control
	 *
	 * @param Control $control Toggling Control
	 * @return \FML\Script\Features\Toggle
	 */
	public function setToggledControl(Control $control) {
		$control->checkId();
		$this->toggledControl = $control;
		return $this;
	}

	/**
	 * Set the Label Name
	 *
	 * @param string $labelName Script Label Name
	 * @return \FML\Script\Features\Toggle
	 */
	public function setLabelName($labelName) {
		$this->labelName = (string) $labelName;
		return $this;
	}

	/**
	 * Set only Show
	 *
	 * @param bool $onlyShow Whether it should only Show the Control but not toggle
	 * @return \FML\Script\Features\Toggle
	 */
	public function setOnlyShow($onlyShow) {
		$this->onlyShow = (bool) $onlyShow;
		return $this;
	}

	/**
	 * Set only Hide
	 *
	 * @param bool $onlyHide Whether it should only Hide the Control but not toggle
	 * @return \FML\Script\Features\Toggle
	 */
	public function setOnlyHide($onlyHide) {
		$this->onlyHide = (bool) $onlyHide;
		return $this;
	}

	/**
	 *
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$script->appendGenericScriptLabel($this->labelName, $this->getScriptText());
		return $this;
	}

	/**
	 * Get the Script Text
	 *
	 * @return string
	 */
	protected function getScriptText() {
		$togglingControlId = $this->togglingControl->getId(true);
		$toggledControlId = $this->toggledControl->getId(true);
		$visibility = '!ToggleControl.Visible';
		if ($this->onlyShow) {
			$visibility = 'True';
		}
		else if ($this->onlyHide) {
			$visibility = 'False';
		}
		$scriptText = "
if (Event.Control.ControlId == \"{$togglingControlId}\") {
	declare ToggleControl = Page.GetFirstChild(\"{$toggledControlId}\");
	ToggleControl.Visible = {$visibility};
}";
		return $scriptText;
	}
}
