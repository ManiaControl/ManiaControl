<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for a Control related script
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ControlScript extends ScriptFeature {
	/*
	 * Protected properties
	 */
	/** @var Control $control */
	protected $control = null;
	protected $labelName = null;
	protected $text = null;

	/**
	 * Construct a new Control Script
	 *
	 * @param Control $control   Event Control
	 * @param string  $text      Script text
	 * @param string  $labelName (optional) Script Label name
	 */
	public function __construct(Control $control, $text, $labelName = ScriptLabel::MOUSECLICK) {
		$this->setControl($control);
		$this->setText($text);
		$this->setLabelName($labelName);
	}

	/**
	 * Set the Control
	 *
	 * @param Control $control Event Control
	 * @return static
	 */
	public function setControl(Control $control) {
		$this->control = $control->checkId();
		$this->updateScriptEvents();
		return $this;
	}

	/**
	 * Set the script text
	 *
	 * @param string $text Script text
	 * @return static
	 */
	public function setText($text) {
		$this->text = (string)$text;
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
		$this->updateScriptEvents();
		return $this;
	}

	/**
	 * Enable Script Events on the Control if needed
	 */
	protected function updateScriptEvents() {
		if (!$this->control || !ScriptLabel::isEventLabel($this->labelName)) {
			return;
		}
		if ($this->control instanceof Scriptable) {
			$this->control->setScriptEvents(true);
		}
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$isolated = !ScriptLabel::isEventLabel($this->labelName);
		$script->appendGenericScriptLabel($this->labelName, $this->buildScriptText(), $isolated);
		return $this;
	}

	/**
	 * Build the script text for the Control
	 *
	 * @return string
	 */
	protected function buildScriptText() {
		$controlId  = $this->control->getId(true);
		$scriptText = '';
		$closeBlock = false;
		if (ScriptLabel::isEventLabel($this->labelName)) {
			$scriptText .= '
if (Event.ControlId == "' . $controlId . '") {
declare Control <=> Event.Control;';
			$closeBlock = true;
		} else {
			$scriptText .= '
declare Control <=> Page.GetFirstChild("' . $controlId . '");';
		}
		$class = $this->control->getManiaScriptClass();
		$name  = preg_replace('/^CMl/', '', $class, 1);
		$scriptText .= '
declare ' . $name . ' <=> (Control as ' . $class . ');
';
		$scriptText .= $this->text . '
';
		if ($closeBlock) {
			$scriptText .= '}';
		}
		return $scriptText;
	}
}
