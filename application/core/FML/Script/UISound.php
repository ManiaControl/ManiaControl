<?php

namespace FML\Script;

/**
 * Class for EUISound Variants
 *
 * @author steeffeen
 */
class UISound {
	/**
	 * Constants
	 */
	const SOUND_Bonus = 'Bonus';
	const SOUND_Capture = 'Capture';
	const SOUND_Checkpoint = 'Checkpoint';
	const SOUND_Combo = 'Combo';
	const SOUND_Custom1 = 'Custom1';
	const SOUND_Custom2 = 'Custom2';
	const SOUND_Custom3 = 'Custom3';
	const SOUND_Custom4 = 'Custom4';
	const SOUND_Default = 'Default';
	const SOUND_EndMatch = 'EndMatch';
	const SOUND_EndRound = 'EndRound';
	const SOUND_Finish = 'Finish';
	const SOUND_FirstHit = 'FirstHit';
	const SOUND_Notice = 'Notice';
	const SOUND_PhaseChange = 'PhaseChange';
	const SOUND_PlayerEliminated = 'PlayerEliminated';
	const SOUND_PlayerHit = 'PlayerHit';
	const SOUND_PlayersRemaining = 'PlayersRemaining';
	const SOUND_RankChange = 'RankChange';
	const SOUND_Record = 'Record';
	const SOUND_ScoreProgress = 'ScoreProgress';
	const SOUND_Silence = 'Silence';
	const SOUND_StartMatch = 'StartMatch';
	const SOUND_StartRound = 'StartRound';
	const SOUND_TieBreakPoint = 'TieBreakPoint';
	const SOUND_TiePoint = 'TiePoint';
	const SOUND_TimeOut = 'TimeOut';
	const SOUND_VictoryPoint = 'VictoryPoint';
	const SOUND_Warning = 'Warning';
	
	/**
	 * Public Properties
	 */
	public $name = self::SOUND_Default;
	public $variant = 0;
	public $volume = 1.;

	/**
	 * Create a new EUISound Instance
	 *
	 * @param string $name
	 * @param int $variant
	 * @param real $volume
	 */
	public function __construct($name = self::SOUND_Default, $variant = 0, $volume = 1.) {
		$this->name = $name;
		$this->variant = $variant;
		$this->volume = $volume;
	}
}
