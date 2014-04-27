<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Script\Builder;


/**
 * Script Feature for opening the Map Info
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapInfo extends ScriptFeature {
	/*
	 * Protected Properties
	 */
	protected $control = null;
	protected $labelName = null;

	/**
	 * Construct a new Map Info Feature
	 *
	 * @param Control $control (optional) Map Info Control
	 * @param string $labelName (optional) Script Label Name
	 */
	public function __construct(Control $control, $labelName = ScriptLabel::MOUSECLICK) {
		$this->setControl($control);
		$this->setLabelName($labelName);
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
		if ($this->control) {
			// Control event
			$controlId = Builder::escapeText($this->control->getId());
			$scriptText = "
if (Event.Control.ControlId == \"{$controlId}\") {
	ShowCurChallengeCard();
}";
		}
		else {
			// Other
			$scriptText = "
ShowCurChallengeCard();";
		}
		return $scriptText;
	}
}
