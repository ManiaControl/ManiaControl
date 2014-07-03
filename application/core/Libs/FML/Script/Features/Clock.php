<?php

namespace FML\Script\Features;

use FML\Controls\Label;
use FML\Script\Script;
use FML\Script\ScriptInclude;
use FML\Script\ScriptLabel;

/**
 * Script Feature showing the current time on a Label
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Clock extends ScriptFeature {
	/*
	 * Protected properties
	 */
	/** @var Label $label */
	protected $label = null;
	protected $showSeconds = null;
	protected $showFullDate = null;

	/**
	 * Construct a new Clock Feature
	 *
	 * @param Label $label        (optional) Clock Label
	 * @param bool  $showSeconds  (optional) Whether the seconds should be shown
	 * @param bool  $showFullDate (optional) Whether the date should be shown
	 */
	public function __construct(Label $label = null, $showSeconds = true, $showFullDate = false) {
		if (!is_null($label)) {
			$this->setLabel($label);
		}
		$this->setShowSeconds($showSeconds);
		$this->setShowFullDate($showFullDate);
	}

	/**
	 * Set the Label
	 *
	 * @param Label $label Clock Label
	 * @return static
	 */
	public function setLabel(Label $label) {
		$this->label = $label->checkId();
		return $this;
	}

	/**
	 * Set whether seconds should be shown
	 *
	 * @param bool $showSeconds Whether seconds should be shown
	 * @return static
	 */
	public function setShowSeconds($showSeconds) {
		$this->showSeconds = (bool)$showSeconds;
		return $this;
	}

	/**
	 * Set whether the full date should be shown
	 *
	 * @param bool $showFullDate Whether the full date should be shown
	 * @return static
	 */
	public function setShowFullDate($showFullDate) {
		$this->showFullDate = (bool)$showFullDate;
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$script->setScriptInclude(ScriptInclude::TEXTLIB);
		$script->appendGenericScriptLabel(ScriptLabel::TICK, $this->getScriptText(), true);
		return $this;
	}

	/**
	 * Get the script text
	 *
	 * @return string
	 */
	protected function getScriptText() {
		$controlId  = $this->label->getId(true, true);
		$scriptText = "
declare ClockLabel <=> (Page.GetFirstChild({$controlId}) as CMlLabel);
declare TimeText = CurrentLocalDateText;";
		if (!$this->showSeconds) {
			$scriptText .= "
TimeText = TextLib::SubText(TimeText, 0, 16);";
		}
		if (!$this->showFullDate) {
			$scriptText .= "
TimeText = TextLib::SubText(TimeText, 11, 9);";
		}
		$scriptText .= "
ClockLabel.Value = TimeText;";
		return $scriptText;
	}
}
