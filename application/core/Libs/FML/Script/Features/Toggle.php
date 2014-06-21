<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for toggling Controls
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Toggle extends ScriptFeature {
	/*
	 * Protected properties
	 */
	/** @var Control $togglingControl */
	protected $togglingControl = null;
	/** @var Control $toggledControl */
	protected $toggledControl = null;
	protected $labelName = null;
	protected $onlyShow = null;
	protected $onlyHide = null;

	/**
	 * Construct a new Toggle Feature
	 *
	 * @param Control $togglingControl (optional) Toggling Control
	 * @param Control $toggledControl  (optional) Toggled Control
	 * @param string  $labelName       (optional) Script Label name
	 * @param bool    $onlyShow        (optional) Whether it should only show the Control but not toggle
	 * @param bool    $onlyHide        (optional) Whether it should only hide the Control but not toggle
	 */
	public function __construct(Control $togglingControl = null, Control $toggledControl = null, $labelName = ScriptLabel::MOUSECLICK,
	                            $onlyShow = false, $onlyHide = false) {
		if (!is_null($togglingControl)) {
			$this->setTogglingControl($togglingControl);
		}
		if (!is_null($toggledControl)) {
			$this->setToggledControl($toggledControl);
		}
		$this->setLabelName($labelName);
		$this->setOnlyShow($onlyShow);
		$this->setOnlyHide($onlyHide);
	}

	/**
	 * Set the toggling Control
	 *
	 * @param Control $control Toggling Control
	 * @return \FML\Script\Features\Toggle|static
	 */
	public function setTogglingControl(Control $control) {
		$control->checkId();
		if ($control instanceof Scriptable) {
			$control->setScriptEvents(true);
		}
		$this->togglingControl = $control;
		return $this;
	}

	/**
	 * Set the toggled Control
	 *
	 * @param Control $control Toggling Control
	 * @return \FML\Script\Features\Toggle|static
	 */
	public function setToggledControl(Control $control) {
		$this->toggledControl = $control->checkId();
		return $this;
	}

	/**
	 * Set the label name
	 *
	 * @param string $labelName Script Label Name
	 * @return \FML\Script\Features\Toggle|static
	 */
	public function setLabelName($labelName) {
		$this->labelName = (string)$labelName;
		return $this;
	}

	/**
	 * Set to only show
	 *
	 * @param bool $onlyShow Whether it should only show the Control but not toggle
	 * @return \FML\Script\Features\Toggle|static
	 */
	public function setOnlyShow($onlyShow) {
		$this->onlyShow = (bool)$onlyShow;
		return $this;
	}

	/**
	 * Set to only hide
	 *
	 * @param bool $onlyHide Whether it should only hide the Control but not toggle
	 * @return \FML\Script\Features\Toggle|static
	 */
	public function setOnlyHide($onlyHide) {
		$this->onlyHide = (bool)$onlyHide;
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$script->appendGenericScriptLabel($this->labelName, $this->getScriptText());
		return $this;
	}

	/**
	 * Get the script text
	 *
	 * @return string
	 */
	protected function getScriptText() {
		$togglingControlId = $this->togglingControl->getId(true, true);
		$toggledControlId  = $this->toggledControl->getId(true, true);
		$visibility        = '!ToggleControl.Visible';
		if ($this->onlyShow) {
			$visibility = 'True';
		} else if ($this->onlyHide) {
			$visibility = 'False';
		}
		return "
if (Event.Control.ControlId == {$togglingControlId}) {
	declare ToggleControl = Page.GetFirstChild({$toggledControlId});
	ToggleControl.Visible = {$visibility};
}";
	}
}
