<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for a Control-related Script
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ControlScript extends ScriptFeature {
	/*
	 * Protected Properties
	 */
	/** @var Control $control */
	protected $control = null;
	protected $labelName = null;
	protected $text = null;
	protected $isolated = null;

	/**
	 * Construct a new Custom Script Text
	 *
	 * @param Control $control   Event Control
	 * @param string  $text      Script Text
	 * @param string  $labelName (optional) Script Label Name
	 * @param bool    $isolated  (optional) Whether to isolate the Script Text
	 */
	public function __construct(Control $control, $text, $labelName = ScriptLabel::MOUSECLICK, $isolated = true) {
		$this->setControl($control);
		$this->setText($text);
		$this->setLabelName($labelName);
		$this->setIsolated($isolated);
	}

	/**
	 * Set the Control
	 *
	 * @param Control $control Custom Control
	 * @return \FML\Script\Features\ControlScript
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
	 * Set the Script Text
	 *
	 * @param string $text Script Text
	 * @return \FML\Script\Features\ControlScript
	 */
	public function setText($text) {
		$this->text = (string)$text;
		return $this;
	}

	/**
	 * Set the Label Name
	 *
	 * @param string $labelName Script Label Name
	 * @return \FML\Script\Features\ControlScript
	 */
	public function setLabelName($labelName) {
		$this->labelName = $labelName;
		return $this;
	}

	/**
	 * Set whether the Script should be isolated
	 *
	 * @param bool $isolated Whether to isolate the Script Text
	 * @return \FML\Script\Features\ControlScript
	 */
	public function setIsolated($isolated = true) {
		$this->isolated = (bool)$isolated;
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$script->appendGenericScriptLabel($this->labelName, $this->getEncapsulatedText(), $this->isolated);
		return $this;
	}

	/**
	 * Get the Script Text encapsulated for the Control Event
	 *
	 * @return string
	 */
	protected function getEncapsulatedText() {
		$controlId  = $this->control->getId(true);
		$scriptText = "
if (Event.ControlId == \"{$controlId}\") {
	{$this->text}
}";
		return $scriptText;
	}
}
