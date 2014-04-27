<?php

namespace FML\Script\Features;


use FML\Script\Script;
use FML\Script\ScriptLabel;


use FML\Controls\Label;
use FML\Script\ScriptInclude;

/**
 * Script Feature showing the current Time on a Label
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Clock extends ScriptFeature {
	/*
	 * Protected Properties
	 */
	protected $label = null;
	protected $showSeconds = null;
	protected $showFullDate = null;

	/**
	 * Construct a new Clock Feature
	 *
	 * @param Label $label (optional) Clock Label
	 * @param bool $showSeconds (optional) Whether the Seconds should be shown
	 * @param bool $showFullDate (optional) Whether the Date should be shown
	 */
	public function __construct(Label $label = null, $showSeconds = true, $showFullDate = false) {
		$this->setLabel($label);
		$this->setShowSeconds($showSeconds);
		$this->setShowFullDate($showFullDate);
	}

	/**
	 * Set the Label
	 *
	 * @param Label $label Clock Label
	 * @return \FML\Script\Features\Clock
	 */
	public function setLabel(Label $label) {
		$label->checkId();
		$this->label = $label;
		return $this;
	}

	/**
	 * Set whether the Seconds should be shown
	 *
	 * @param bool $showSeconds Whether the Seconds should be shown
	 * @return \FML\Script\Features\Clock
	 */
	public function setShowSeconds($showSeconds) {
		$this->showSeconds = (bool) $showSeconds;
		return $this;
	}

	/**
	 * Set whether the Full Date should be shown
	 *
	 * @param bool $showFullDate Whether the Full Date should be shown
	 * @return \FML\Script\Features\Clock
	 */
	public function setShowFullDate($showFullDate) {
		$this->showFullDate = (bool) $showFullDate;
		return $this;
	}

	/**
	 *
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$script->setScriptInclude(ScriptInclude::TEXTLIB);
		$script->appendGenericScriptLabel(ScriptLabel::TICK, $this->getScriptText(), true);
		return $this;
	}

	/**
	 * Get the Script Text
	 *
	 * @return string
	 */
	protected function getScriptText() {
		$controlId = $this->label->getId(true);
		$scriptText = "
declare ClockLabel <=> (Page.GetFirstChild(\"{$controlId}\") as CMlLabel);
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
