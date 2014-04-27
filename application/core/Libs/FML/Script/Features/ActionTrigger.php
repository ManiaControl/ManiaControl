<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Script\Builder;


/**
 * Script Feature for triggering a Page Action
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ActionTrigger extends ScriptFeature {
	/*
	 * Protected Properties
	 */
	protected $actionName = null;
	protected $control = null;
	protected $labelName = null;

	/**
	 * Construct a new Action Trigger Feature
	 *
	 * @param string $actionName (optional) Triggered Action
	 * @param Control $control (optional) Action Control
	 * @param string $labelName (optional) Script Label Name
	 */
	public function __construct($actionName = null, Control $control = null, $labelName = ScriptLabel::MOUSECLICK) {
		$this->setActionName($actionName);
		$this->setControl($control);
		$this->setLabelName($labelName);
	}

	/**
	 * Set the Action to trigger
	 *
	 * @param string $actionName
	 * @return \FML\Script\Features\ActionTrigger
	 */
	public function setActionName($actionName) {
		$this->actionName = $actionName;
		return $this;
	}

	/**
	 * Set the Control
	 * 
	 * @param Control $control Action Control
	 * @return \FML\Script\Features\ActionTrigger
	 */
	public function setControl(Control $control) {
		$control->checkId();
		$control->setScriptEvents(true);
		$this->control = $control;
		return $this;
	}

	/**
	 * Set the Label Name
	 *
	 * @param string $labelName Script Label Name
	 * @return \FML\Script\Features\ActionTrigger
	 */
	public function setLabelName($labelName) {
		$this->labelName = $labelName;
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
		$actionName = Builder::escapeText($this->actionName);
		if ($this->control) {
			// Control event
			$controlId = Builder::escapeText($this->control->getId());
			$scriptText = "
if (Event.Control.ControlId == \"{$controlId}\") {
	TriggerPageAction(\"{$actionName}\");
}";
		}
		else {
			// Other
			$scriptText = "
TriggerPageAction(\"{$actionName}\");";
		}
		return $scriptText;
	}
}
