<?php

namespace ManiaControl\Callbacks\Models;

/**
 * Base Model Class for Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2015 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class RecordCallback extends BaseCallback {
	/*
	 * Constants
	 */
	const CHECKPOINT = 'RecordCallback.Checkpoint';
	const FINISH     = 'RecordCallback.Finish';
	const LAPFINISH  = 'RecordCallback.LapFinish';

	/*
	 * Public Properties
	 */
	public $isEndRace = null;
	public $isEndLap = null;
	public $time = null;
	public $lapTime = null;
	public $checkpoint = null;
	public $lapCheckpoint = null;
	public $lap = null;
	public $blockId = null;
}
