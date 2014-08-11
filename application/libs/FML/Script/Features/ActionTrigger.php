<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for triggering a manialink page action
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ActionTrigger extends ScriptFeature {
	/*
	 * Protected properties
	 */
	protected $actionName = null;
	/** @var Control $control */
	protected $control = null;
	protected $labelName = null;

	/**
	 * Construct a new Action Trigger Feature
	 *
	 * @param string  $actionName (optional) Triggered action
	 * @param Control $control    (optional) Action Control
	 * @param string  $labelName  (optional) Script Label name
	 */
	public function __construct($actionName = null, Control $control = null, $labelName = ScriptLabel::MOUSECLICK) {
		if ($actionName !== null) {
			$this->setActionName($actionName);
		}
		if ($control !== null) {
			$this->setControl($control);
		}
		if ($labelName !== null) {
			$this->setLabelName($labelName);
		}
	}

	/**
	 * Set the action to trigger
	 *
	 * @param string $actionName
	 * @return static
	 */
	public function setActionName($actionName) {
		$this->actionName = (string)$actionName;
		return $this;
	}

	/**
	 * Set the Control
	 *
	 * @param Control $control Action Control
	 * @return static
	 */
	public function setControl(Control $control) {
		$control->checkId();
		if ($control instanceof Scriptable) {
			$control->setScriptEvents(true);
		}
		$this->control = $control;
		return $this;
	}

	/**
	 * Set the label name
	 *
	 * @param string $labelName Script Label name
	 * @return static
	 */
	public function setLabelName($labelName) {
		$this->labelName = (string)$labelName;
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
		$actionName = Builder::escapeText($this->actionName, true);
		if ($this->control) {
			// Control event
			$controlId  = Builder::escapeText($this->control->getId(), true);
			$scriptText = "
if (Event.Control.ControlId == {$controlId}) {
	TriggerPageAction({$actionName});
}";
		} else {
			// Other
			$scriptText = "
TriggerPageAction({$actionName});";
		}
		return $scriptText;
	}
}
