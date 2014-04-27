<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Script\Builder;

/**
 * Script Feature for playing an UI Sound
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class UISound extends ScriptFeature {
	/*
	 * Constants
	 */
	const Bonus = 'Bonus';
	const Capture = 'Capture';
	const Checkpoint = 'Checkpoint';
	const Combo = 'Combo';
	const Custom1 = 'Custom1';
	const Custom2 = 'Custom2';
	const Custom3 = 'Custom3';
	const Custom4 = 'Custom4';
	const Default_ = 'Default';
	const EndMatch = 'EndMatch';
	const EndRound = 'EndRound';
	const Finish = 'Finish';
	const FirstHit = 'FirstHit';
	const Notice = 'Notice';
	const PhaseChange = 'PhaseChange';
	const PlayerEliminated = 'PlayerEliminated';
	const PlayerHit = 'PlayerHit';
	const PlayersRemaining = 'PlayersRemaining';
	const RankChange = 'RankChange';
	const Record = 'Record';
	const ScoreProgress = 'ScoreProgress';
	const Silence = 'Silence';
	const StartMatch = 'StartMatch';
	const StartRound = 'StartRound';
	const TieBreakPoint = 'TieBreakPoint';
	const TiePoint = 'TiePoint';
	const TimeOut = 'TimeOut';
	const VictoryPoint = 'VictoryPoint';
	const Warning = 'Warning';
	
	/*
	 * Protected Properties
	 */
	protected $soundName = null;
	protected $control = null;
	protected $variant = 0;
	protected $volume = 1.;
	protected $labelName = null;

	/**
	 * Construct a new UISound Feature
	 *
	 * @param string $soundName (optional) Played Sound
	 * @param Control $control (optional) Action Control
	 * @param int $variant (optional) Sound Variant
	 * @param string $labelName (optional) Script Label Name
	 */
	public function __construct($soundName = null, Control $control = null, $variant = 0, $labelName = ScriptLabel::MOUSECLICK) {
		$this->setSoundName($soundName);
		$this->setControl($control);
		$this->setVariant($variant);
		$this->setLabelName($labelName);
	}

	/**
	 * Set the Sound to play
	 *
	 * @param string $soundName Sound Name
	 * @return \FML\Script\Features\UISound
	 */
	public function setSoundName($soundName) {
		$this->soundName = (string) $soundName;
		return $this;
	}

	/**
	 * Set the Control
	 *
	 * @param Control $control Action Control
	 * @return \FML\Script\Features\UISound
	 */
	public function setControl(Control $control) {
		$control->checkId();
		$control->setScriptEvents(true);
		$this->control = $control;
		return $this;
	}

	/**
	 * Set the Sound Variant
	 *
	 * @param int $variant Sound Variant
	 * @return \FML\Script\Features\UISound
	 */
	public function setVariant($variant) {
		$this->variant = (int) $variant;
		return $this;
	}

	/**
	 * Set the Volume
	 *
	 * @param float $volume Sound Volume
	 * @return \FML\Script\Features\UISound
	 */
	public function setVolume($volume) {
		$this->volume = (float) $volume;
		return $this;
	}

	/**
	 * Set the Label Name
	 *
	 * @param string $labelName Script Label Name
	 * @return \FML\Script\Features\UISound
	 */
	public function setLabelName($labelName) {
		$this->labelName = (string) $labelName;
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
	PlayUiSound(CMlScriptIngame::EUISound::{$this->soundName}, {$this->variant}, {$this->volume});
}";
		}
		else {
			// Other
			$scriptText = "
PlayUiSound(CMlScriptIngame::EUISound::{$this->soundName}, {$this->variant}, {$this->volume});";
		}
		return $scriptText;
	}
}
